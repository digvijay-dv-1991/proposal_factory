<?php

namespace App\Livewire;

use App\Models\Opportunity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.capture-deck')]
class OpportunityBoard extends Component
{
    /**
     * Fixed phase column order, matching CLAUDE.md's Pre-Solicitation ->
     * Source Selection pipeline.
     *
     * @var array<int, string>
     */
    public const PHASES = [
        'Pre-Solicitation',
        'RFI',
        'Draft RFP',
        'RFP Released',
        'Submitted',
        'Source Selection',
    ];

    public const SECTIONS = [
        'CBRN', 'Digitization', 'DoD Intelligence - Ops', 'FOCI', 'General', 'Health', 'DFaaS', 'MISC',
    ];

    public const FIT_LEVELS = ['Strong', 'Moderate', 'No Fit'];

    /**
     * Phases hidden from the default board (no phase filter selected) —
     * opportunities in these phases are already reachable through the
     * dedicated Submitted/No Bid tabs, so showing them as board columns too
     * is redundant. Explicitly selecting one of these via the Status
     * dropdown still shows it (matches the reference).
     *
     * @var array<int, string>
     */
    public const HIDDEN_DEFAULT_PHASES = ['Submitted', 'Source Selection'];

    /**
     * The top view tabs: [tab key => [kicker, label]]. Monitoring/Pending/
     * More Info/Bid/No Bid all just point at $decisionFilter and Submitted
     * points at $phaseFilter — the SAME properties the "Decision" and
     * "Status" dropdowns use, so a tab and a dropdown can never disagree.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const TABS = [
        'pipeline' => ['Pipeline', 'Opportunity List'],
        'added_today' => ['24-Hour Intake', 'Added Today'],
        'monitoring' => ['Watch List', 'Monitoring'],
        'pending' => ['Awaiting Decision', 'Pending'],
        'more_info' => ['Needs Review', 'More Info Needed'],
        'bid' => ['Active Pursuits', 'Bid'],
        'no_bid' => ['Archive', 'No Bid'],
        'submitted' => ['Completed', 'Submitted'],
    ];

    public string $search = '';

    public string $agencyFilter = '';

    public string $phaseFilter = '';

    public string $decisionFilter = '';

    public string $focusFilter = '';

    public string $fitFilter = '';

    public string $bidTypeFilter = '';

    public string $sectionFilter = '';

    public bool $addedToday = false;

    /**
     * Which dialog overlay is open (Contract Vehicles / Competitors /
     * Partners), or '' for none. Matches the reference: these three use a
     * true popup dialog over the board.
     */
    public string $activeOverlay = '';

    /**
     * 'board', 'daily_brief', or a calendar mode ('solicitation'/'events') —
     * these all swap the main content area in place, matching the
     * reference's renderDailyBrief()/renderCalendar() (they replace the
     * board content directly, they don't open a dialog). Never a URL change.
     */
    public string $activeView = 'board';

    /**
     * The opportunity currently open in the detail modal, or null when it's
     * closed. Editing an existing card sets this; "+ New Opportunity" opens
     * the same modal with $creatingOpportunity instead. Synced to the
     * ?opportunity= query string so emailed reminder links
     * (route('opportunities.index', ['opportunity' => $id])) open straight
     * into the right opportunity.
     */
    #[Url(as: 'opportunity')]
    public ?int $activeOpportunityId = null;

    public bool $creatingOpportunity = false;

    public bool $showNotifications = false;

    public function toggleNotifications(): void
    {
        $this->showNotifications = ! $this->showNotifications;
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return auth()->user()?->notifications()->latest()->limit(20)->get() ?? collect();
    }

    #[Computed]
    public function unreadNotificationCount(): int
    {
        return auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    /**
     * Every notification we send (front-end invites, admin-only bid
     * reminders) is built through Filament's fluent Notification API so it
     * renders in both the front-end bell here and the admin panel's bell —
     * clicking one just follows its own action link, wherever that points.
     */
    public function openNotification(string $id): void
    {
        $notification = auth()->user()?->notifications()->whereKey($id)->first();
        $notification?->markAsRead();

        $url = $notification?->data['actions'][0]['url'] ?? null;

        $this->showNotifications = false;

        if ($url !== null) {
            $this->redirect($url);
        }
    }

    public function markAllNotificationsRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Live-refresh hook for the socket-pushed notification event (see
     * resources/js/app.js's Echo listener) — #[Computed] properties are
     * memoized only per-request, so the empty handler alone is enough to
     * force notifications()/unreadNotificationCount() to re-read fresh on
     * this round trip.
     */
    #[On('notifications-updated')]
    public function refreshNotifications(): void {}

    public function openOpportunity(int $id): void
    {
        $this->activeOpportunityId = $id;
        $this->creatingOpportunity = false;
    }

    public function newOpportunity(): void
    {
        $this->activeOpportunityId = null;
        $this->creatingOpportunity = true;
    }

    #[On('opportunity-modal-closed')]
    public function closeOpportunityModal(): void
    {
        $this->activeOpportunityId = null;
        $this->creatingOpportunity = false;
    }

    public function openOverlay(string $overlay): void
    {
        $this->activeOverlay = $overlay;
    }

    public function closeOverlay(): void
    {
        $this->activeOverlay = '';
    }

    public function overlayTitle(): string
    {
        return match ($this->activeOverlay) {
            'contract_vehicles' => 'Contract Vehicles',
            'competitors' => 'Competitors',
            'partners' => 'Teaming Network',
            'suggestions' => 'Suggestions',
            default => '',
        };
    }

    public function showDailyBrief(): void
    {
        $this->activeView = 'daily_brief';
    }

    public function showCalendar(string $mode): void
    {
        $this->activeView = $mode === 'events' ? 'events' : 'solicitation';
    }

    public function showBoard(): void
    {
        $this->activeView = 'board';
    }

    /**
     * A tab is just a shortcut that sets the same properties the dropdowns
     * use, so picking a tab and picking the matching dropdown value are
     * indistinguishable to the query.
     */
    public function selectTab(string $tab): void
    {
        // Capture "is this tab already active" before resetting state below,
        // so clicking the active tab again toggles back to Pipeline instead
        // of silently re-selecting itself.
        $reselecting = $this->activeTab() === $tab;

        $this->addedToday = false;
        $this->decisionFilter = '';
        $this->phaseFilter = '';

        if ($reselecting) {
            return;
        }

        match ($tab) {
            'added_today' => $this->addedToday = true,
            'monitoring' => $this->decisionFilter = 'Monitoring',
            'pending' => $this->decisionFilter = 'Pending',
            'more_info' => $this->decisionFilter = 'More Info',
            'bid' => $this->decisionFilter = 'Bid',
            'no_bid' => $this->decisionFilter = 'No Bid',
            'submitted' => $this->phaseFilter = 'Submitted',
            default => null, // 'pipeline': the reset above already cleared everything.
        };
    }

    public function filterBySection(string $section): void
    {
        $this->sectionFilter = $this->sectionFilter === $section ? '' : $section;
    }

    public function filterByPhase(string $phase): void
    {
        $this->phaseFilter = $this->phaseFilter === $phase ? '' : $phase;
    }

    /**
     * Which tab reads as "active", derived from the same properties the
     * dropdowns use rather than tracked separately.
     */
    #[Computed]
    public function activeTab(): string
    {
        return match (true) {
            $this->addedToday => 'added_today',
            $this->decisionFilter === 'Monitoring' => 'monitoring',
            $this->decisionFilter === 'Pending' => 'pending',
            $this->decisionFilter === 'More Info' => 'more_info',
            $this->decisionFilter === 'Bid' => 'bid',
            $this->decisionFilter === 'No Bid' => 'no_bid',
            $this->phaseFilter === 'Submitted' && $this->decisionFilter === '' => 'submitted',
            default => 'pipeline',
        };
    }

    /**
     * Whether the active tab renders as a flat row list (No Bid,
     * Submitted) rather than the phase-column board.
     */
    public function isRowListTab(): bool
    {
        return in_array($this->activeTab(), ['no_bid', 'submitted'], true);
    }

    public function isNoBidTab(): bool
    {
        return $this->activeTab() === 'no_bid';
    }

    /**
     * The dropdown/section filters shared by every tab's query, i.e.
     * everything except decision/phase/added-today, which the tabs
     * themselves already express through those same properties.
     *
     * @return Builder<Opportunity>
     */
    private function filteredQuery(): Builder
    {
        return Opportunity::query()
            ->search($this->search)
            ->when($this->agencyFilter !== '', fn ($q) => $q->where('agency', $this->agencyFilter))
            ->when($this->focusFilter !== '', fn ($q) => $q->whereJsonContains('focus', $this->focusFilter))
            ->when($this->bidTypeFilter !== '', fn ($q) => $q->where('set_aside', $this->bidTypeFilter))
            ->when($this->sectionFilter !== '', fn ($q) => $q->where('origin', $this->sectionFilter))
            ->when($this->fitFilter !== '', function ($q) {
                match ($this->fitFilter) {
                    'Strong' => $q->where('go_strength', '>=', 70),
                    'Moderate' => $q->whereBetween('go_strength', [50, 69]),
                    default => $q->where('go_strength', '<', 50),
                };
            });
    }

    /**
     * @return EloquentCollection<int, Opportunity>
     */
    #[Computed]
    public function opportunities(): EloquentCollection
    {
        $activeTab = $this->activeTab();

        $query = $this->applyTab($this->filteredQuery(), $activeTab)
            ->when($this->phaseFilter !== '' && $activeTab !== 'submitted', fn ($q) => $q->where('phase', $this->phaseFilter));

        // No Bid's row-list sorts newest decision first (tie-broken
        // alphabetically), overriding the board's usual go_strength order —
        // matches the reference's dedicated No Bid view.
        if ($activeTab === 'no_bid') {
            return $query->orderByRaw('COALESCE(decision_date, date_added) DESC')->orderBy('name')->get();
        }

        return $query->orderByDesc('go_strength')->get();
    }

    /**
     * A single tab's query condition, shared by both the current listing
     * and every tab's count so the two can never drift apart.
     *
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    private function applyTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'pipeline' => $query->where('decision', '!=', 'No Bid')
                ->where(fn ($q) => $q->whereNull('discovered_at')->orWhere('discovered_at', '<', now()->subDay())),
            'added_today' => $query->where('discovered_at', '>=', now()->subDay()),
            'monitoring' => $query->where('decision', 'Monitoring'),
            'pending' => $query->where('decision', 'Pending'),
            'more_info' => $query->where('decision', 'More Info'),
            'bid' => $query->where('decision', 'Bid'),
            'no_bid' => $query->where('decision', 'No Bid'),
            'submitted' => $query->where('phase', 'Submitted'),
            default => $query,
        };
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        $base = $this->filteredQuery();

        return collect(array_keys(self::TABS))->mapWithKeys(fn (string $tab) => [
            $tab => $this->applyTab(clone $base, $tab)->count(),
        ])->all();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function sectionCounts(): array
    {
        return collect(self::SECTIONS)->mapWithKeys(fn (string $section) => [
            $section => $this->opportunities()->where('origin', $section)->count(),
        ])->all();
    }

    /**
     * Top masthead metrics: active pipeline size, how many of those have a
     * validated source link, total active pipeline value, and how many are
     * due within 14 days.
     *
     * @return array{active: int, validated: int, pipeline_value: float, due_soon: int}
     */
    #[Computed]
    public function metrics(): array
    {
        $active = $this->activeOpportunities();

        return [
            'active' => $active->count(),
            'validated' => $active->filter(fn (Opportunity $o) => $o->has_required_source)->count(),
            'pipeline_value' => (float) $active->sum(fn (Opportunity $o) => (float) $o->value),
            'due_soon' => $active->filter(fn (Opportunity $o) => $o->response_due !== null
                && $o->response_due->between(now(), now()->addDays(14)))->count(),
        ];
    }

    /**
     * @return EloquentCollection<int, Opportunity>
     */
    private function activeOpportunities(): EloquentCollection
    {
        return Opportunity::query()->where('decision', '!=', 'No Bid')->get();
    }

    /**
     * Opportunities grouped into their phase column, in fixed pipeline
     * order, each with its column total value.
     *
     * @return Collection<string, array{items: EloquentCollection<int, Opportunity>, total: float}>
     */
    #[Computed]
    public function board(): Collection
    {
        $grouped = $this->opportunities()->groupBy('phase');
        $empty = $this->opportunities()->take(0);

        return collect(self::PHASES)->mapWithKeys(function (string $phase) use ($grouped, $empty) {
            $items = $grouped->get($phase) ?? $empty;

            return [$phase => ['items' => $items, 'total' => (float) $items->sum(fn (Opportunity $o) => (float) $o->value)]];
        });
    }

    /**
     * The phase columns actually rendered on the board — all 6 when a
     * specific phase is selected via the Status dropdown, or the 4
     * non-hidden ones on the default (unfiltered) board.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function visiblePhases(): array
    {
        return $this->phaseFilter !== ''
            ? [$this->phaseFilter]
            : array_values(array_diff(self::PHASES, self::HIDDEN_DEFAULT_PHASES));
    }

    public function visiblePhaseCount(): int
    {
        return count($this->visiblePhases());
    }

    /**
     * Inline grid-template-columns/min-width style for the unfiltered
     * board, sized to fit however many phase columns are visible.
     */
    public function boardGridStyle(): string
    {
        $count = $this->visiblePhaseCount();

        return sprintf(
            'grid-template-columns: repeat(%d, minmax(245px, 1fr)); min-width: %dpx;',
            $count,
            $count * 245 + ($count - 1) * 18
        );
    }

    /**
     * @return array{items: EloquentCollection<int, Opportunity>, total: float}
     */
    public function boardColumn(string $phase): array
    {
        return $this->board()[$phase];
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function agencyOptions(): array
    {
        return Opportunity::query()->distinct()->orderBy('agency')->pluck('agency')->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function bidTypeOptions(): array
    {
        return Opportunity::query()->whereNotNull('set_aside')->distinct()->orderBy('set_aside')->pluck('set_aside')->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function focusOptions(): array
    {
        return Opportunity::query()->pluck('focus')
            ->filter()
            ->flatten(1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.opportunity-board');
    }
}
