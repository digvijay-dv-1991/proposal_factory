<?php

use App\Console\Commands\FetchSamGovOpportunities;
use App\Console\Commands\SendBidDueDateAlerts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Client is US-based (Eastern); 8am covers their whole workday for the
// 14/7/3/1-day and due-day Bid reminders. See BidAlertService::TIMEZONE.
Schedule::command(SendBidDueDateAlerts::class)
    ->dailyAt('08:00')
    ->timezone('America/New_York');

// Same 8am Eastern slot as the client's own "Morning Recon" workflow this
// replaces the manual half of. Run `php artisan opportunities:fetch-sam-gov`
// directly (add --limit=N locally to stay under a low-tier API quota).
// Schedule::command(FetchSamGovOpportunities::class)
//     ->dailyAt('08:00')
//     ->timezone('America/New_York');
