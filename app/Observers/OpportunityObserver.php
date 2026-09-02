<?php

namespace App\Observers;

use App\Models\Opportunity;
use App\Services\BidAlertService;

class OpportunityObserver
{
    public function __construct(private readonly BidAlertService $bidAlerts) {}

    /**
     * Fires the initial Bid alert the moment an opportunity's decision is
     * saved as "Bid" — whether that happens through the confirm-decision
     * dialog in OpportunityModal or any other path that saves the model.
     */
    public function updated(Opportunity $opportunity): void
    {
        if ($opportunity->wasChanged('decision') && $opportunity->decision === 'Bid') {
            $this->bidAlerts->sendMovedToBidAlert($opportunity);
        }
    }
}
