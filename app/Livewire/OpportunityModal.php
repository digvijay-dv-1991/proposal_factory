<?php

namespace App\Livewire;

use App\Jobs\GenerateAiAnalysis;
use App\Jobs\GenerateCompetitiveAnalysis;
use App\Models\Opportunity;
use App\Models\OpportunityBidComment;
use App\Models\OpportunityBidInvite;
use App\Models\OpportunityContact;
use App\Models\OpportunityPartner;
use App\Models\OpportunityUpdate;
use App\Models\User;
use App\Notifications\InvitedToReviewOpportunity;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class OpportunityModal extends Component
{
    use WithFileUploads;

    /**
     * The 8 tabs, in order. Section Strategy was removed (origin is now a
     * plain field on Overview); Incumbent was folded into Competitive
     * Analysis as a second panel on that tab; Bid Strength was removed
     * (go_strength has no dedicated tab); Contracting Officers moved last.
     *
     * @var array<int, string>
     */
    public const TABS = [
        'Overview',
        'Bid Decision',
        'Gap Analysis',
        'Competitive Analysis',
        'Teaming',
        'Attachments',
        'Updates',
        'AI Analysis',
        'Contracting Officers',
    ];

    public const DECISIONS = ['Pending', 'More Info', 'Monitoring', 'Shape', 'Bid', 'No Bid'];

    public const COMPETITIVE_POSITIONS = ['Strong', 'Moderate', 'Weak', 'Unknown'];

    public const GAP_STATUSES = ['Not Assessed', 'Open', 'Mitigation in Progress', 'Accepted', 'Resolved'];

    /**
     * @var array<int, string>
     */
    public const SET_ASIDE_OPTIONS = [
        'Full & Open', 'Small Business Set-Aside', '8(a)', '8(a) Sole Source', 'SDVOSB', 'VOSB', 'WOSB',
        'EDWOSB', 'HUBZone', 'SDB', 'AbilityOne', 'Local Area Set-Aside', 'Partial Set-Aside', 'Total Set-Aside',
        'Sole Source', 'Single Award IDIQ', 'Multiple Award IDIQ', 'IDIQ Task Order', 'GWAC', 'BPA', 'BPA Call',
        'BOA', 'GSA MAS', 'Federal Supply Schedule', 'MAC', 'MATOC', 'SATOC', 'OTA', 'Commercial Solutions Opening',
        'SBIR', 'STTR', 'Prize Challenge', 'Cooperative Agreement', 'Grant', 'Other', 'TBD',
    ];

    /**
     * Set-aside values that indicate a vehicle-access eligibility question
     * rather than a general set-aside eligibility question (Summary panel's
     * "Eligibility" row, matching the reference's inline list).
     *
     * @var array<int, string>
     */
    private const VEHICLE_SET_ASIDES = [
        'Full & Open', 'GSA MAS', 'Federal Supply Schedule', 'GWAC', 'Multiple Award IDIQ', 'Single Award IDIQ',
        'IDIQ Task Order', 'BPA', 'BPA Call', 'BOA', 'MAC', 'MATOC', 'SATOC', 'OTA', 'Commercial Solutions Opening',
        'Prize Challenge',
    ];

    /**
     * Every single-record editable field across Overview, Gap Analysis, and
     * Competitive Analysis (which now also covers Incumbent) — bound
     * through $form (a plain array) rather than directly to $opportunity's
     * attributes. Livewire's Eloquent-model synthesizer only ships an
     * identity reference (class + primary key) to the browser for a bound
     * model property, never its attribute values, so nested wire:model
     * bindings against a model instance render blank once the page's JS
     * hydrates (confirmed by testing — an explicit server-rendered value=""
     * isn't enough, since Livewire's client re-sync overwrites it from that
     * same reference-only snapshot). A plain array is fully value-serialized
     * instead of reference-serialized, so it hydrates correctly.
     *
     * @var array<int, string>
     */
    private const FORM_FIELDS = [
        'name', 'agency', 'agency_subsection', 'solicitation', 'naics', 'psc', 'value', 'vehicle', 'set_aside',
        'phase', 'decision', 'date_added', 'response_due', 'link', 'govwin_link', 'alqimi_sme',
        'origin', 'next_action', 'action_due', 'source_description', 'source_requirements',
        'gap', 'gap_mitigation', 'gap_owner', 'gap_status', 'competitive_analysis', 'competitive_discriminators',
        'competitive_next_action', 'incumbent', 'competitors', 'competitive_position', 'teaming', 'rfp_instructions',
        'rfp_sections', 'rfp_format', 'evaluation_factors', 'probability', 'incumbent_contract',
        'incumbent_award_value', 'incumbent_period', 'incumbent_brief', 'incumbent_performance',
        'incumbent_strengths', 'incumbent_weaknesses', 'incumbent_customer_relationship', 'incumbent_source',
    ];

    /**
     * @var array<int, string>
     */
    private const DATE_FIELDS = ['date_added', 'response_due', 'action_due'];

    /**
     * Attachment types accepted for upload, matching the plan's "common
     * document/image types" call, and the 10MB-per-file limit.
     */
    private const ATTACHMENT_MIME_TYPES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg';

    private const ATTACHMENT_MAX_KB = 10240;

    public ?int $opportunityId = null;

    public Opportunity $opportunity;

    public string $activeTab = 'Overview';

    /**
     * @var array<string, mixed>
     */
    public array $form = [];

    public bool $showDecisionDialog = false;

    public string $pendingDecision = '';

    public string $previousDecision = '';

    public string $decisionWho = '';

    public string $decisionReason = '';

    public string $decisionPriorityInput = '';

    /**
     * Set when the confirm-decision dialog was just used, so Save knows to
     * write a decision_history row. The reference dedupes history writes by
     * comparing a decisionSignature string across saves; that field wasn't
     * ported (see the plan's §2 notes), so this in-session flag serves the
     * same purpose without it.
     */
    public bool $decisionJustConfirmed = false;

    /**
     * Contracting Officers tab — array-backed, see $form's docblock for why.
     *
     * @var array<int, array{id: int, name: string, title: ?string, organization: ?string, role: ?string, email: ?string, phone: ?string}>
     */
    public array $contacts = [];

    /**
     * @var array{name: string, title: string, organization: string, role: string, email: string, phone: string}
     */
    public array $newContact = ['name' => '', 'title' => '', 'organization' => '', 'role' => '', 'email' => '', 'phone' => ''];

    /**
     * Teaming tab — array-backed, see $form's docblock for why.
     *
     * @var array<int, array{id: int, company: string, role: ?string, status: ?string, capability: ?string, rationale: ?string}>
     */
    public array $partners = [];

    /**
     * @var array{company: string, role: string, status: string, capability: string, rationale: string}
     */
    public array $newPartner = ['company' => '', 'role' => '', 'status' => '', 'capability' => '', 'rationale' => ''];

    /**
     * Updates tab — array-backed, see $form's docblock for why.
     *
     * @var array<int, array{id: int, date: ?string, text: string}>
     */
    public array $updatesList = [];

    /**
     * @var array{date: string, text: string}
     */
    public array $newUpdate = ['date' => '', 'text' => ''];

    /**
     * @var TemporaryUploadedFile|null
     */
    public $newAttachmentFile = null;

    /**
     * Bid Decision tab — array-backed, see $form's docblock for why.
     *
     * @var array<int, array{id: int, user_id: int, name: string, invited_by: string, last_invited_at: Carbon}>
     */
    public array $bidInvites = [];

    public string $inviteUserId = '';

    /**
     * Overview's Bid / No Bid discussion chat — array-backed, see $form's
     * docblock for why.
     *
     * @var array<int, array{id: int, name: string, text: string, created_at: Carbon}>
     */
    public array $bidComments = [];

    public string $newBidComment = '';

    /**
     * Name typed by someone posting to the chat while not logged in — the
     * chat is open to everyone until the auth/roles phase starts.
     */
    public string $guestName = '';

    /**
     * Seconds a "Generate with AI" run is allowed to poll before the UI
     * gives up and reports a failure instead of polling forever.
     */
    private const GENERATION_TIMEOUT_SECONDS = 90;

    public bool $generatingAiAnalysis = false;

    public ?string $aiAnalysisStartedAt = null;

    public bool $generatingCompetitiveAnalysis = false;

    public ?string $competitiveAnalysisStartedAt = null;

    public function mount(?int $opportunityId = null): void
    {
        $this->opportunityId = $opportunityId;

        // Only 'attachments' is eager-loaded: it's the one relation read via
        // its Eloquent collection directly in the view (bubble count,
        // Overview's Source Attachments panel, the Attachments tab list).
        // contacts/partners/updates are always re-queried fresh into their
        // own array state by refresh*() below, so eager-loading them here
        // would just be wasted queries never read from the relation cache.
        $this->opportunity = $opportunityId !== null
            ? Opportunity::query()->with('attachments')->findOrFail($opportunityId)
            : new Opportunity([
                'external_id' => 'OPP-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4)),
                'phase' => 'Pre-Solicitation',
                'decision' => 'Pending',
                'origin' => 'General',
                'incumbent' => 'Unknown',
                'competitive_position' => 'Unknown',
                'gap_status' => 'Not Assessed',
                'probability' => 0,
                'go_strength' => 0,
                'value' => 0,
                'date_added' => now()->toDateString(),
            ]);

        $this->previousDecision = $this->opportunity->decision;
        $this->form = $this->buildForm();

        if ($this->opportunity->exists) {
            $this->refreshContacts();
            $this->refreshPartners();
            $this->refreshUpdates();
            $this->refreshBidInvites();
            $this->refreshBidComments();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildForm(): array
    {
        $form = [];

        foreach (self::FORM_FIELDS as $field) {
            $value = $this->opportunity->{$field};
            $form[$field] = in_array($field, self::DATE_FIELDS, true) ? $value?->format('Y-m-d') : $value;
        }

        return $form;
    }

    private function refreshContacts(): void
    {
        $this->contacts = $this->opportunity->contacts()->get()
            ->map(fn (OpportunityContact $contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'title' => $contact->title,
                'organization' => $contact->organization,
                'role' => $contact->role,
                'email' => $contact->email,
                'phone' => $contact->phone,
            ])
            ->all();
    }

    private function refreshPartners(): void
    {
        $this->partners = $this->opportunity->partners()->get()
            ->map(fn (OpportunityPartner $partner): array => [
                'id' => $partner->id,
                'company' => $partner->company,
                'role' => $partner->role,
                'status' => $partner->status,
                'capability' => $partner->capability,
                'rationale' => $partner->rationale,
            ])
            ->all();
    }

    private function refreshUpdates(): void
    {
        $this->updatesList = $this->opportunity->updates()->orderByDesc('date')->get()
            ->map(fn (OpportunityUpdate $update): array => [
                'id' => $update->id,
                'date' => $update->date?->format('Y-m-d'),
                'text' => $update->text,
            ])
            ->all();
    }

    private function refreshBidInvites(): void
    {
        $this->bidInvites = $this->opportunity->bidInvites()->with(['invitee', 'inviter'])->latest('updated_at')->get()
            ->map(function (OpportunityBidInvite $invite): array {
                $inviter = $invite->inviter;

                return [
                    'id' => $invite->id,
                    'user_id' => $invite->user_id,
                    'name' => $invite->invitee->name,
                    'invited_by' => $inviter !== null ? $inviter->name : 'Unknown',
                    'last_invited_at' => $invite->updated_at,
                ];
            })
            ->all();
    }

    private function refreshBidComments(): void
    {
        $this->bidComments = $this->opportunity->bidComments()->with('user')->oldest()->get()
            ->map(function (OpportunityBidComment $comment): array {
                $commenter = $comment->user;
                $name = $commenter !== null ? $commenter->name : $comment->author_name;

                return [
                    'id' => $comment->id,
                    'name' => $name ?: 'Guest',
                    'text' => $comment->text,
                    'created_at' => $comment->created_at,
                ];
            })
            ->all();
    }

    /**
     * Users not already invited to review this opportunity, for the invite
     * dropdown — already-invited users are reinvited from the list instead.
     *
     * @return array<int, User>
     */
    #[Computed]
    public function inviteCandidates(): array
    {
        $invitedUserIds = array_column($this->bidInvites, 'user_id');

        return User::query()->whereNotIn('id', $invitedUserIds)->orderBy('name')->get()->all();
    }

    public function inviteUser(): void
    {
        $this->validate(['inviteUserId' => ['required', Rule::exists('users', 'id')]]);

        $invite = $this->opportunity->bidInvites()->create([
            'user_id' => $this->inviteUserId,
            'invited_by' => auth()->id(),
        ]);

        $this->notifyInvitee($invite);
        $this->inviteUserId = '';
        $this->refreshBidInvites();
    }

    public function reinviteUser(int $inviteId): void
    {
        $invite = $this->opportunity->bidInvites()->whereKey($inviteId)->first();

        if ($invite === null) {
            return;
        }

        $invite->invited_by = auth()->id();
        $invite->touch();

        $this->notifyInvitee($invite);
        $this->refreshBidInvites();
    }

    private function notifyInvitee(OpportunityBidInvite $invite): void
    {
        $invite->loadMissing('invitee');
        $currentUser = auth()->user();
        $invitedByName = $currentUser !== null ? $currentUser->name : 'A teammate';

        $invite->invitee->notify(new InvitedToReviewOpportunity($this->opportunity, $invitedByName));
    }

    public function postBidComment(): void
    {
        $user = auth()->user();

        $rules = ['newBidComment' => ['required', 'string', 'max:2000']];

        if ($user === null) {
            $rules['guestName'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules);

        $this->opportunity->bidComments()->create([
            'user_id' => $user?->id,
            'author_name' => $user !== null ? null : $this->guestName,
            'text' => $this->newBidComment,
        ]);

        $this->newBidComment = '';
        $this->refreshBidComments();
    }

    public function generateAiAnalysis(): void
    {
        if (! $this->opportunity->exists || $this->generatingAiAnalysis) {
            return;
        }

        if (! RateLimiter::attempt('ai-analysis:'.$this->opportunity->id, 5, fn () => true, 600)) {
            $this->addError('aiAnalysis', 'Too many generation attempts for this opportunity — wait a few minutes and try again.');

            return;
        }

        GenerateAiAnalysis::dispatch($this->opportunity->id);
        $this->generatingAiAnalysis = true;
        $this->aiAnalysisStartedAt = now()->toIso8601String();
    }

    public function pollAiAnalysis(): void
    {
        if (! $this->generatingAiAnalysis || $this->aiAnalysisStartedAt === null) {
            return;
        }

        $this->opportunity->refresh();
        $startedAt = Carbon::parse($this->aiAnalysisStartedAt);
        $generatedAt = $this->opportunity->ai_analysis_generated_at;

        if ($generatedAt !== null && $generatedAt->greaterThanOrEqualTo($startedAt)) {
            $this->generatingAiAnalysis = false;

            return;
        }

        if ($startedAt->diffInSeconds(now()) > self::GENERATION_TIMEOUT_SECONDS) {
            $this->generatingAiAnalysis = false;
            $this->addError('aiAnalysis', 'AI generation is taking too long or failed. Please try again.');
        }
    }

    public function generateCompetitiveAnalysis(): void
    {
        if (! $this->opportunity->exists || $this->generatingCompetitiveAnalysis) {
            return;
        }

        if (! RateLimiter::attempt('competitive-analysis:'.$this->opportunity->id, 5, fn () => true, 600)) {
            $this->addError('competitiveAnalysis', 'Too many generation attempts for this opportunity — wait a few minutes and try again.');

            return;
        }

        GenerateCompetitiveAnalysis::dispatch($this->opportunity->id);
        $this->generatingCompetitiveAnalysis = true;
        $this->competitiveAnalysisStartedAt = now()->toIso8601String();
    }

    public function pollCompetitiveAnalysis(): void
    {
        if (! $this->generatingCompetitiveAnalysis || $this->competitiveAnalysisStartedAt === null) {
            return;
        }

        $this->opportunity->refresh();
        $startedAt = Carbon::parse($this->competitiveAnalysisStartedAt);
        $generatedAt = $this->opportunity->competitive_analysis_generated_at;

        if ($generatedAt !== null && $generatedAt->greaterThanOrEqualTo($startedAt)) {
            $this->generatingCompetitiveAnalysis = false;
            $this->form = $this->buildForm();

            return;
        }

        if ($startedAt->diffInSeconds(now()) > self::GENERATION_TIMEOUT_SECONDS) {
            $this->generatingCompetitiveAnalysis = false;
            $this->addError('competitiveAnalysis', 'AI generation is taking too long or failed. Please try again.');
        }
    }

    public function addContact(): void
    {
        $this->validate([
            'newContact.name' => ['required', 'string', 'max:255'],
            'newContact.title' => ['nullable', 'string', 'max:255'],
            'newContact.organization' => ['nullable', 'string', 'max:255'],
            'newContact.role' => ['nullable', 'string', 'max:255'],
            'newContact.email' => ['nullable', 'email', 'max:255'],
            'newContact.phone' => ['nullable', 'string', 'max:255'],
        ]);

        $this->opportunity->contacts()->create($this->newContact);
        $this->newContact = ['name' => '', 'title' => '', 'organization' => '', 'role' => '', 'email' => '', 'phone' => ''];
        $this->refreshContacts();
    }

    public function saveContact(int $index): void
    {
        $this->validate([
            "contacts.{$index}.name" => ['required', 'string', 'max:255'],
            "contacts.{$index}.title" => ['nullable', 'string', 'max:255'],
            "contacts.{$index}.organization" => ['nullable', 'string', 'max:255'],
            "contacts.{$index}.role" => ['nullable', 'string', 'max:255'],
            "contacts.{$index}.email" => ['nullable', 'email', 'max:255'],
            "contacts.{$index}.phone" => ['nullable', 'string', 'max:255'],
        ]);

        $row = $this->contacts[$index];
        $this->opportunity->contacts()->whereKey($row['id'])->update([
            'name' => $row['name'],
            'title' => $row['title'],
            'organization' => $row['organization'],
            'role' => $row['role'],
            'email' => $row['email'],
            'phone' => $row['phone'],
        ]);
    }

    public function removeContact(int $id): void
    {
        $this->opportunity->contacts()->whereKey($id)->delete();
        $this->refreshContacts();
    }

    public function addPartner(): void
    {
        $this->validate([
            'newPartner.company' => ['required', 'string', 'max:255'],
            'newPartner.role' => ['nullable', 'string', 'max:255'],
            'newPartner.status' => ['nullable', 'string', 'max:255'],
            'newPartner.capability' => ['nullable', 'string'],
            'newPartner.rationale' => ['nullable', 'string'],
        ]);

        $this->opportunity->partners()->create($this->newPartner);
        $this->newPartner = ['company' => '', 'role' => '', 'status' => '', 'capability' => '', 'rationale' => ''];
        $this->refreshPartners();
    }

    public function savePartner(int $index): void
    {
        $this->validate([
            "partners.{$index}.company" => ['required', 'string', 'max:255'],
            "partners.{$index}.role" => ['nullable', 'string', 'max:255'],
            "partners.{$index}.status" => ['nullable', 'string', 'max:255'],
            "partners.{$index}.capability" => ['nullable', 'string'],
            "partners.{$index}.rationale" => ['nullable', 'string'],
        ]);

        $row = $this->partners[$index];
        $this->opportunity->partners()->whereKey($row['id'])->update([
            'company' => $row['company'],
            'role' => $row['role'],
            'status' => $row['status'],
            'capability' => $row['capability'],
            'rationale' => $row['rationale'],
        ]);
    }

    public function removePartner(int $id): void
    {
        $this->opportunity->partners()->whereKey($id)->delete();
        $this->refreshPartners();
    }

    public function addUpdate(): void
    {
        $this->validate([
            'newUpdate.date' => ['required', 'date'],
            'newUpdate.text' => ['required', 'string'],
        ]);

        $this->opportunity->updates()->create($this->newUpdate);
        $this->newUpdate = ['date' => '', 'text' => ''];
        $this->refreshUpdates();
    }

    public function saveUpdate(int $index): void
    {
        $this->validate([
            "updatesList.{$index}.date" => ['required', 'date'],
            "updatesList.{$index}.text" => ['required', 'string'],
        ]);

        $row = $this->updatesList[$index];
        $this->opportunity->updates()->whereKey($row['id'])->update([
            'date' => $row['date'],
            'text' => $row['text'],
        ]);
    }

    public function removeUpdate(int $id): void
    {
        $this->opportunity->updates()->whereKey($id)->delete();
        $this->refreshUpdates();
    }

    public function uploadAttachment(): void
    {
        $this->validate([
            'newAttachmentFile' => ['required', 'file', 'mimes:'.self::ATTACHMENT_MIME_TYPES, 'max:'.self::ATTACHMENT_MAX_KB],
        ]);

        $path = $this->newAttachmentFile->store('opportunity-attachments', 'public');

        $this->opportunity->attachments()->create([
            'original_name' => $this->newAttachmentFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $this->newAttachmentFile->getMimeType(),
            'size' => $this->newAttachmentFile->getSize(),
            'type' => strtoupper((string) $this->newAttachmentFile->getClientOriginalExtension()).' attachment',
        ]);

        $this->newAttachmentFile = null;
        $this->opportunity->unsetRelation('attachments');
        $this->opportunity->load('attachments');
    }

    public function removeAttachment(int $id): void
    {
        $attachment = $this->opportunity->attachments()->whereKey($id)->first();

        if ($attachment !== null) {
            if ($attachment->path !== null) {
                Storage::disk('public')->delete($attachment->path);
            }
            $attachment->delete();
        }

        $this->opportunity->unsetRelation('attachments');
        $this->opportunity->load('attachments');
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Keeps $opportunity (used everywhere read-only: Summary panel,
     * Capability Fit, exports-to-be) mirroring $form live as the user
     * types, and intercepts the decision field changing to Bid/No Bid so it
     * routes through the confirmation dialog instead of applying instantly
     * (matches the reference's ds.onchange handler in bindInputs).
     */
    public function updated(string $property, mixed $value): void
    {
        if (! str_starts_with($property, 'form.')) {
            return;
        }

        $field = substr($property, 5);

        if ($field === 'decision') {
            $this->handleDecisionChanged($value);

            return;
        }

        if ($this->opportunity->isFillable($field)) {
            $this->opportunity->{$field} = $value;
        }
    }

    private function handleDecisionChanged(string $value): void
    {
        if (in_array($value, ['Bid', 'No Bid'], true)) {
            if ($value === $this->previousDecision) {
                return;
            }

            $this->pendingDecision = $value;
            $this->decisionWho = (string) ($this->opportunity->decision_by ?? '');
            $this->decisionReason = (string) ($this->opportunity->decision_comment ?? '');
            $this->decisionPriorityInput = $value === 'Bid' && $this->opportunity->bid_priority
                ? (string) $this->opportunity->bid_priority
                : '';
            $this->showDecisionDialog = true;

            return;
        }

        $this->opportunity->decision = $value;
        $this->opportunity->decision_by = null;
        $this->opportunity->decision_comment = null;
        $this->opportunity->decision_date = null;
        $this->opportunity->bid_priority = null;
        $this->previousDecision = $value;
    }

    public function confirmDecision(): void
    {
        $this->validate([
            'decisionWho' => ['required', 'string', 'max:255'],
            'decisionReason' => ['required', 'string'],
            'decisionPriorityInput' => $this->pendingDecision === 'Bid'
                ? ['required', Rule::in(['1', '2', '3'])]
                : ['nullable'],
        ], [
            'decisionWho.required' => 'Who ordered this is required.',
            'decisionReason.required' => 'A reason is required.',
            'decisionPriorityInput.required' => 'Select a Bid priority level.',
        ]);

        $this->opportunity->decision = $this->pendingDecision;
        $this->opportunity->bid_priority = $this->pendingDecision === 'Bid' ? (int) $this->decisionPriorityInput : null;
        $this->opportunity->decision_by = $this->decisionWho;
        $this->opportunity->decision_comment = $this->decisionReason;
        $this->opportunity->decision_date = now();

        $this->form['decision'] = $this->pendingDecision;
        $this->previousDecision = $this->pendingDecision;
        $this->pendingDecision = '';
        $this->showDecisionDialog = false;
        $this->decisionJustConfirmed = true;
    }

    public function cancelDecision(): void
    {
        $this->opportunity->decision = $this->previousDecision;
        $this->form['decision'] = $this->previousDecision;
        $this->pendingDecision = '';
        $this->showDecisionDialog = false;
    }

    public function save(): void
    {
        $this->validate($this->rules());

        $this->opportunity->fill($this->form);

        if (! $this->opportunity->has_required_source) {
            $this->addError('form.link', 'Every opportunity requires a credible source URL before it can be saved.');
            $this->activeTab = 'Overview';

            return;
        }

        if (in_array($this->opportunity->decision, ['Bid', 'No Bid'], true)
            && (blank($this->opportunity->decision_comment) || blank($this->opportunity->decision_by))) {
            $this->addError('form.decision', 'A Bid or No Bid decision requires who ordered it and the reason.');
            $this->activeTab = 'Overview';

            return;
        }

        $this->opportunity->save();

        if ($this->decisionJustConfirmed) {
            $this->opportunity->decisionHistory()->create([
                'date' => $this->opportunity->decision_date,
                'decision' => $this->opportunity->decision,
                'by' => $this->opportunity->decision_by,
                'reason' => $this->opportunity->decision_comment,
            ]);
            $this->decisionJustConfirmed = false;
        }

        $this->dispatch('opportunity-modal-closed');
    }

    public function delete(): void
    {
        if ($this->opportunity->exists) {
            $this->opportunity->delete();
        }

        $this->dispatch('opportunity-modal-closed');
    }

    public function close(): void
    {
        $this->dispatch('opportunity-modal-closed');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.agency' => ['required', 'string', 'max:255'],
            'form.agency_subsection' => ['nullable', 'string', 'max:255'],
            'form.solicitation' => ['nullable', 'string', 'max:255'],
            'form.naics' => ['nullable', 'string', 'max:255'],
            'form.psc' => ['nullable', 'string', 'max:255'],
            'form.value' => ['required', 'numeric', 'min:0'],
            'form.vehicle' => ['nullable', 'string', 'max:255'],
            // Not Rule::in(SET_ASIDE_OPTIONS): the column is a free string
            // with no DB-level enum (unlike phase/decision/origin/gap_status/
            // competitive_position, which are real DB enums below). Seeded
            // and historical data holds free-text values outside the
            // dropdown's curated list — 26 of the 71 seeded opportunities
            // hit this — so saving must accept any existing value, not just
            // the ones offered for new selections.
            'form.set_aside' => ['nullable', 'string', 'max:255'],
            'form.phase' => ['required', Rule::in(OpportunityBoard::PHASES)],
            'form.decision' => ['required', Rule::in(self::DECISIONS)],
            'form.date_added' => ['nullable', 'date'],
            'form.response_due' => ['nullable', 'date'],
            'form.link' => ['nullable', 'url', 'max:2048'],
            'form.govwin_link' => ['nullable', 'url', 'max:2048'],
            'form.alqimi_sme' => ['nullable', 'string', 'max:255'],
            'form.origin' => ['required', Rule::in(OpportunityBoard::SECTIONS)],
            'form.next_action' => ['nullable', 'string'],
            'form.action_due' => ['nullable', 'date'],
            'form.source_description' => ['nullable', 'string'],
            'form.source_requirements' => ['nullable', 'string'],
            'form.gap' => ['nullable', 'string'],
            'form.gap_mitigation' => ['nullable', 'string'],
            'form.gap_owner' => ['nullable', 'string', 'max:255'],
            'form.gap_status' => ['nullable', Rule::in(self::GAP_STATUSES)],
            'form.competitive_analysis' => ['nullable', 'string'],
            'form.competitive_discriminators' => ['nullable', 'string'],
            'form.competitive_next_action' => ['nullable', 'string'],
            'form.incumbent' => ['nullable', 'string', 'max:255'],
            'form.competitors' => ['nullable', 'string'],
            'form.competitive_position' => ['nullable', Rule::in(self::COMPETITIVE_POSITIONS)],
            'form.teaming' => ['nullable', 'string'],
            'form.rfp_instructions' => ['nullable', 'string'],
            'form.rfp_sections' => ['nullable', 'string'],
            'form.rfp_format' => ['nullable', 'string', 'max:255'],
            'form.evaluation_factors' => ['nullable', 'string'],
            'form.probability' => ['required', 'integer', 'min:0', 'max:100'],
            'form.incumbent_contract' => ['nullable', 'string', 'max:255'],
            'form.incumbent_award_value' => ['nullable', 'string', 'max:255'],
            'form.incumbent_period' => ['nullable', 'string', 'max:255'],
            'form.incumbent_brief' => ['nullable', 'string'],
            'form.incumbent_performance' => ['nullable', 'string'],
            'form.incumbent_strengths' => ['nullable', 'string'],
            'form.incumbent_weaknesses' => ['nullable', 'string'],
            'form.incumbent_customer_relationship' => ['nullable', 'string'],
            'form.incumbent_source' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractKeyPoints(?string $text): array
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', (string) $text));

        if ($normalized === '') {
            return [];
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $normalized) ?: [];

        return array_values(array_filter(array_map('trim', $sentences)));
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function solicitationKeyPoints(): array
    {
        return $this->extractKeyPoints($this->opportunity->source_description ?: $this->opportunity->description);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function requirementKeyPoints(): array
    {
        return $this->extractKeyPoints($this->opportunity->source_requirements ?: $this->opportunity->scope);
    }

    public function eligibilityLabel(): string
    {
        return in_array($this->opportunity->set_aside, self::VEHICLE_SET_ASIDES, true)
            ? 'Review vehicle access'
            : 'Review eligibility';
    }

    public function strategicPriorityLabel(): string
    {
        return (float) $this->opportunity->value >= 5_000_000 ? 'High' : 'Moderate';
    }

    public function render(): View
    {
        return view('livewire.opportunity-modal');
    }
}
