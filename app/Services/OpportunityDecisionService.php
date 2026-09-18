<?php

namespace App\Services;

use App\Models\Opportunity;

/**
 * Single entry point for changing an opportunity's decision — shared by the
 * admin panel's form and its quick "Change Decision" action so the
 * who/reason/priority rule (Bid/No Bid only) and the decision_history write
 * live in one place instead of being re-implemented per caller. Mirrors
 * OpportunityModal::handleDecisionChanged()/confirmDecision() on the public
 * board.
 */
class OpportunityDecisionService
{
    private const DECISIONS_REQUIRING_REASON = ['Bid', 'No Bid'];

    public function apply(Opportunity $opportunity, string $decision, ?string $by = null, ?string $reason = null, ?int $bidPriority = null): void
    {
        if (! $this->requiresReason($decision)) {
            $opportunity->decision = $decision;
            $opportunity->decision_by = null;
            $opportunity->decision_comment = null;
            $opportunity->decision_date = null;
            $opportunity->bid_priority = null;
            $opportunity->save();

            return;
        }

        $opportunity->decision = $decision;
        $opportunity->decision_by = $by;
        $opportunity->decision_comment = $reason;
        $opportunity->decision_date = now();
        $opportunity->bid_priority = $decision === 'Bid' ? $bidPriority : null;
        $opportunity->save();

        $this->recordHistory($opportunity);
    }

    public function recordHistory(Opportunity $opportunity): void
    {
        $opportunity->decisionHistory()->create([
            'date' => $opportunity->decision_date ?? now(),
            'decision' => $opportunity->decision,
            'by' => $opportunity->decision_by,
            'reason' => $opportunity->decision_comment,
        ]);
    }

    public function requiresReason(?string $decision): bool
    {
        return in_array($decision, self::DECISIONS_REQUIRING_REASON, true);
    }
}
