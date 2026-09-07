<?php

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
