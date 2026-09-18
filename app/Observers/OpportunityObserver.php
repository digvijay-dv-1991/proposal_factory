<?php

namespace App\Observers;

use App\Events\OpportunityBoardChanged;
use App\Livewire\OpportunityBoard;
use App\Models\Opportunity;
use App\Services\BidAlertService;
use Illuminate\Support\Facades\Cache;

class OpportunityObserver
{
    public function __construct(private readonly BidAlertService $bidAlerts) {}

    /**
     * A brand-new opportunity should appear live on every connected board.
     */
    public function created(Opportunity $opportunity): void
    {
        OpportunityBoardChanged::dispatch();
    }

    /**
     * Fires the initial Bid alert the moment an opportunity's decision is
     * saved as "Bid" — whether that happens through the confirm-decision
     * dialog in OpportunityModal or any other path that saves the model.
     * Also signals every connected board to refresh live when a decision
     * or phase change moves the opportunity between lists.
     */
    public function updated(Opportunity $opportunity): void
    {
        if ($opportunity->wasChanged('decision') && $opportunity->decision === 'Bid') {
            $this->bidAlerts->sendMovedToBidAlert($opportunity);
        }

        if ($opportunity->wasChanged('decision') || $opportunity->wasChanged('phase')) {
            OpportunityBoardChanged::dispatch();
        }
    }

    /**
     * Invalidates OpportunityBoard's cached filter-dropdown option lists
     * (agency/set-aside/focus) on every create AND update — broader than
     * the decision/phase-only OpportunityBoardChanged broadcast above,
     * since editing an opportunity's agency, set_aside, or focus tags
     * doesn't necessarily change its decision or phase. Unconditional
     * rather than checking wasChanged() on those specific fields: this is
     * a handful of cheap Cache::forget calls, not worth the bookkeeping.
     */
    public function saved(Opportunity $opportunity): void
    {
        Cache::forget(OpportunityBoard::AGENCY_OPTIONS_CACHE_KEY);
        Cache::forget(OpportunityBoard::BID_TYPE_OPTIONS_CACHE_KEY);
        Cache::forget(OpportunityBoard::FOCUS_OPTIONS_CACHE_KEY);
    }
}
