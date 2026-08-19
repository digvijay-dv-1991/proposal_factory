<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'product_alignment',
        'alqimi_sme',
        'section_rationale',
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
            'focus' => 'array',
            'keywords' => 'array',
            'capture_plan' => 'array',
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
        return Attribute::get(function (): string {
            return match (true) {
                $this->go_strength >= 70 => 'Strong',
                $this->go_strength >= 50 => 'Moderate',
                default => 'No Fit',
            };
        });
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

    public function contacts(): HasMany
    {
        return $this->hasMany(OpportunityContact::class);
    }

    public function partners(): HasMany
    {
        return $this->hasMany(OpportunityPartner::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OpportunityAttachment::class);
    }

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
}
