<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $fit
 * @property-read string $fit_class
 * @property-read string $fit_label
 * @property-read string $strength_class
 * @property-read bool $has_required_source
 * @property-read string|null $source_url
 * @property-read bool $has_gap
 * @property-read bool $has_competitive_analysis
 * @property-read array<int, array{label: string, score: int}> $bid_strength_breakdown
 * @property-read array<int, string> $competitor_names
 * @property-read array<int, array{label: string, url: string}> $accessible_links
 * @property-read array<int, array{name: string, url: string, role: string}> $competitor_links
 * @property Carbon|null $date_added
 * @property Carbon|null $response_due
 * @property Carbon|null $release_date
 * @property Carbon|null $discovered_at
 * @property Carbon|null $monitoring_last_checked
 * @property Carbon|null $action_due
 * @property Carbon|null $decision_date
 * @property Carbon|null $ai_analysis_generated_at
 * @property Carbon|null $competitive_analysis_generated_at
 * @property array<int, string>|null $focus
 * @property array<int, string>|null $keywords
 * @property array<int, string>|null $capture_plan
 * @property array<int, array{label: string, url: string}>|null $competitive_analysis_sources
 */
class Opportunity extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'agency',
        'agency_subsection',
        'solicitation',
        'naics',
        'psc',
        'value',
        'vehicle',
        'set_aside',
        'phase',
        'decision',
        'date_added',
        'response_due',
        'response_time',
        'release_date',
        'discovered_at',
        'link',
        'govwin_link',
        'alqimi_sme',
        'origin',
        'focus',
        'keywords',
        'capture_plan',
        'source_verification',
        'source_title_verified',
        'monitoring_last_checked',
        'next_action',
        'action_due',
        'capture_owner',
        'proposal_manager',
        'source_description',
        'source_requirements',
        'description',
        'scope',
        'rfp_instructions',
        'rfp_sections',
        'rfp_format',
        'evaluation_factors',
        'probability',
        'bid_priority',
        'gap',
        'gap_mitigation',
        'gap_owner',
        'gap_status',
        'competitive_analysis',
        'competitive_discriminators',
        'competitive_next_action',
        'competitive_position',
        'competitors',
        'teaming',
        'incumbent',
        'incumbent_contract',
        'incumbent_award_value',
        'incumbent_period',
        'incumbent_brief',
        'incumbent_performance',
        'incumbent_strengths',
        'incumbent_weaknesses',
        'incumbent_customer_relationship',
        'incumbent_source',
        'go_strength',
        'decision_by',
        'decision_comment',
        'decision_date',
        'ai_executive_summary',
        'ai_why_it_matters',
        'ai_red_team_critique',
        'ai_competitive_outlook',
        'ai_analysis_generated_at',
        'competitive_analysis_generated_at',
        'competitive_analysis_sources',
    ];

    /**
     * Fixed sub-score labels for the Bid Strength tab. The reference app
     * never stores these individually — they're computed from go_strength.
     */
    private const BID_STRENGTH_LABELS = [
        'Capability Fit',
        'Eligibility',
        'Past Performance',
        'Customer Position',
        'Competitive Position',
        'Team Readiness',
        'Capture Readiness',
        'Commercial Attractiveness',
        'Evidence Confidence',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'date_added' => 'date',
            'response_due' => 'date',
            'release_date' => 'date',
            'discovered_at' => 'datetime',
            'monitoring_last_checked' => 'date',
            'action_due' => 'date',
            'decision_date' => 'date',
            'ai_analysis_generated_at' => 'datetime',
            'competitive_analysis_generated_at' => 'datetime',
            'focus' => 'array',
            'keywords' => 'array',
            'capture_plan' => 'array',
            'competitive_analysis_sources' => 'array',
            'source_title_verified' => 'boolean',
            'probability' => 'integer',
            'bid_priority' => 'integer',
            'go_strength' => 'integer',
        ];
    }

    /**
     * Strong/Moderate/No Fit, derived from go_strength (matches the
     * reference's fitFromStrength() thresholds: >=70 Strong, >=50 Moderate).
     */
    protected function fit(): Attribute
    {
        return Attribute::get(fn (): string => $this->computeFit());
    }

    private function computeFit(): string
    {
        return match (true) {
            $this->go_strength >= 70 => 'Strong',
            $this->go_strength >= 50 => 'Moderate',
            default => 'No Fit',
        };
    }

    /**
     * The Bid Strength tab's 9 labeled sub-scores, computed from go_strength.
     */
    protected function bidStrengthBreakdown(): Attribute
    {
        return Attribute::get(function (): array {
            return array_map(
                fn (string $label, int $index) => [
                    'label' => $label,
                    'score' => max(35, $this->go_strength - $index * 3),
                ],
                self::BID_STRENGTH_LABELS,
                array_keys(self::BID_STRENGTH_LABELS),
            );
        });
    }

    /**
     * fit-strong/fit-moderate/fit-none, the CSS class for the fit tag.
     */
    protected function fitClass(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->computeFit()) {
            'Strong' => 'fit-strong',
            'Moderate' => 'fit-moderate',
            default => 'fit-none',
        });
    }

    /**
     * pwin-high/pwin-mid/pwin-low, the card's border/background color class
     * (same go_strength thresholds as fit, matching the reference's
     * strengthClass logic in card()).
     */
    protected function strengthClass(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            $this->go_strength >= 70 => 'pwin-high',
            $this->go_strength >= 50 => 'pwin-mid',
            default => 'pwin-low',
        });
    }

    /**
     * Whether an official source link is recorded (matches the reference's
     * hasRequiredSource(), which flags cards missing their required source).
     */
    protected function hasRequiredSource(): Attribute
    {
        return Attribute::get(fn (): bool => $this->computeSourceUrl() !== null);
    }

    /**
     * The validated official source link, preferring the primary link over
     * the secondary GovWin reference (matches requiredSourceUrl() in the
     * reference).
     */
    protected function sourceUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->computeSourceUrl());
    }

    private function computeSourceUrl(): ?string
    {
        foreach ([$this->link, $this->govwin_link] as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_URL) !== false) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * "Strong Fit" / "Moderate Fit" / "No Fit", the label shown on the card.
     */
    protected function fitLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->computeFit()) {
            'Strong' => 'Strong Fit',
            'Moderate' => 'Moderate Fit',
            default => 'No Fit',
        });
    }

    /**
     * Whether a gap analysis has been recorded (drives the "Gap analyzed" /
     * "Gap needed" card flag).
     */
    protected function hasGap(): Attribute
    {
        return Attribute::get(fn (): bool => trim((string) $this->gap) !== '');
    }

    /**
     * Whether any competitive information has been recorded (drives the
     * "Competition analyzed" / "Competition needed" card flag).
     */
    protected function hasCompetitiveAnalysis(): Attribute
    {
        return Attribute::get(fn (): bool => trim((string) $this->competitive_analysis) !== ''
            || trim((string) $this->incumbent) !== ''
            || trim((string) $this->competitors) !== '');
    }

    /**
     * The `competitors` free-text field split into individual company
     * names — one per line if it's already a list, otherwise split on
     * "; " or ", " before a capital letter (matches the reference's
     * competitorItems()).
     */
    protected function competitorNames(): Attribute
    {
        return Attribute::get(function (): array {
            $raw = trim((string) $this->competitors);

            if ($raw === '') {
                return [];
            }

            $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));

            if (count($lines) > 1) {
                return $lines;
            }

            return array_values(array_filter(array_map(
                'trim',
                preg_split('/\s*;\s*|\s*,\s*(?=[A-Z0-9])/', $raw) ?: [],
            )));
        });
    }

    /**
     * The "Accessible Links" panel on the Overview tab — every structured
     * URL field recorded on the opportunity. The reference also regex-scans
     * every free-text field for embedded URLs; that text-mining is not
     * ported, only the same visual panel driven by the actual URL columns.
     */
    protected function accessibleLinks(): Attribute
    {
        return Attribute::get(function (): array {
            $links = [];

            foreach ([
                'Official RFP / Solicitation Source' => $this->link,
                'GovWin Reference (Secondary)' => $this->govwin_link,
                'Incumbent Verification Source' => $this->incumbent_source,
            ] as $label => $value) {
                if ($value !== null && filter_var($value, FILTER_VALIDATE_URL) !== false) {
                    $links[] = ['label' => $label, 'url' => $value];
                }
            }

            return $links;
        });
    }

    /**
     * The "Competitor Links" panel on the Overview tab — matches the
     * incumbent and named competitors against the seeded market-competitor
     * catalog (ported in Day 2) rather than the reference's hardcoded
     * knownCompanyWebsites list, which was never ported as structured data.
     */
    protected function competitorLinks(): Attribute
    {
        return Attribute::get(function (): array {
            $names = array_unique(array_filter([
                ...($this->incumbent && $this->incumbent !== 'Unknown' ? [$this->incumbent] : []),
                ...$this->competitor_names,
            ]));

            if ($names === []) {
                return [];
            }

            $catalog = MarketCompetitor::query()->get(['name', 'url'])->keyBy(
                fn (MarketCompetitor $competitor): string => mb_strtolower($competitor->name)
            );

            $links = [];
            foreach ($names as $name) {
                $match = $catalog->get(mb_strtolower($name));

                if ($match !== null) {
                    $links[] = [
                        'name' => $match->name,
                        'url' => $match->url,
                        'role' => mb_strtolower($name) === mb_strtolower((string) $this->incumbent) ? 'Incumbent' : 'Competitor',
                    ];
                }
            }

            return $links;
        });
    }

    /**
     * @return HasMany<OpportunityContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(OpportunityContact::class);
    }

    /**
     * @return HasMany<OpportunityPartner, $this>
     */
    public function partners(): HasMany
    {
        return $this->hasMany(OpportunityPartner::class);
    }

    /**
     * @return HasMany<OpportunityAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(OpportunityAttachment::class);
    }

    /**
     * @return HasMany<OpportunityUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(OpportunityUpdate::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(OpportunityMilestone::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(OpportunityEvidence::class);
    }

    public function decisionHistory(): HasMany
    {
        return $this->hasMany(OpportunityDecisionHistory::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(OpportunityRelationship::class);
    }

    /**
     * @return HasMany<OpportunityBidInvite, $this>
     */
    public function bidInvites(): HasMany
    {
        return $this->hasMany(OpportunityBidInvite::class);
    }

    /**
     * @return HasMany<OpportunityBidComment, $this>
     */
    public function bidComments(): HasMany
    {
        return $this->hasMany(OpportunityBidComment::class);
    }

    /**
     * Free-text search across name, agency, solicitation number, and NAICS
     * (matches the reference search box).
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('agency', 'like', "%{$term}%")
                ->orWhere('solicitation', 'like', "%{$term}%")
                ->orWhere('naics', 'like', "%{$term}%");
        });
    }
}
