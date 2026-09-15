<?php

namespace App\Observers;

use App\Events\OpportunityBoardChanged;
use App\Models\Opportunity;
use App\Services\BidAlertService;

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
}
