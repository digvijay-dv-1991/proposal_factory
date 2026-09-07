<?php

namespace App\Console\Commands;

use App\Services\BidAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-bid-due-date-alerts')]
#[Description('Email the 14/7/3/1-day and due-day reminders for opportunities currently marked Bid')]
class SendBidDueDateAlerts extends Command
{
    public function handle(BidAlertService $bidAlerts): int
    {
        $bidAlerts->sendDueDateReminders();

        return self::SUCCESS;
    }
}
