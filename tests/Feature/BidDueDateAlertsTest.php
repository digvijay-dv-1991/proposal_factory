<?php

use App\Models\Opportunity;
use App\Models\OpportunityBidAlert;
use App\Models\User;
use App\Notifications\BidDueDateReminder;
use App\Services\BidAlertService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

function makeOpportunity(array $attributes = []): Opportunity
{
    return Opportunity::factory()->create(array_merge([
        'external_id' => (string) Str::uuid(),
        'name' => 'Test Opportunity',
        'agency' => 'Test Agency',
    ], $attributes));
}

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
});

test('moving an opportunity into Bid sends the moved-to-bid alert to every Admin', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $opportunity = makeOpportunity(['decision' => 'Pending']);

    $opportunity->decision = 'Bid';
    $opportunity->save();

    Notification::assertSentTo(
        $admin,
        BidDueDateReminder::class,
        fn (BidDueDateReminder $notification) => (fn () => $this->milestone)->call($notification) === 'moved_to_bid',
    );
});

test('saving an opportunity without changing decision to Bid sends nothing', function () {
    Notification::fake();

    $opportunity = makeOpportunity(['decision' => 'Pending']);

    $opportunity->name = 'Renamed';
    $opportunity->save();

    Notification::assertNothingSent();
});

test('the daily command sends the 14-day reminder exactly when 14 days remain', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $dueDate = Carbon::today(BidAlertService::TIMEZONE)->addDays(14);
    $opportunity = makeOpportunity(['decision' => 'Bid', 'response_due' => $dueDate]);

    app(BidAlertService::class)->sendDueDateReminders();

    Notification::assertSentTo(
        $admin,
        BidDueDateReminder::class,
        fn (BidDueDateReminder $notification) => (fn () => $this->milestone)->call($notification) === 'day_14',
    );

    expect(OpportunityBidAlert::query()
        ->where('opportunity_id', $opportunity->id)
        ->where('milestone', 'day_14')
        ->exists())->toBeTrue();
});

test('the daily command does not resend a milestone already recorded for the current due date', function () {
    Notification::fake();

    User::factory()->create()->assignRole('Admin');

    $dueDate = Carbon::today(BidAlertService::TIMEZONE)->addDays(7);
    makeOpportunity(['decision' => 'Bid', 'response_due' => $dueDate]);

    app(BidAlertService::class)->sendDueDateReminders();
    Notification::assertSentTimes(BidDueDateReminder::class, 1);

    app(BidAlertService::class)->sendDueDateReminders();
    Notification::assertSentTimes(BidDueDateReminder::class, 1);
});

test('editing the due date makes an already-sent milestone eligible again', function () {
    Notification::fake();

    User::factory()->create()->assignRole('Admin');

    $currentDueDate = Carbon::today(BidAlertService::TIMEZONE)->addDays(3);
    $opportunity = makeOpportunity(['decision' => 'Bid', 'response_due' => $currentDueDate]);

    // Simulate the day_3 reminder having already fired for a *previous*
    // due date, before the opportunity's due date was pushed to today's.
    OpportunityBidAlert::create([
        'opportunity_id' => $opportunity->id,
        'milestone' => 'day_3',
        'response_due' => $currentDueDate->copy()->subDays(4),
        'sent_at' => now(),
    ]);

    app(BidAlertService::class)->sendDueDateReminders();

    // The tracking row's response_due no longer matches the opportunity's
    // current one, so the milestone fires again for the new date instead
    // of being treated as a duplicate.
    Notification::assertSentTimes(BidDueDateReminder::class, 1);

    expect(OpportunityBidAlert::query()
        ->where('opportunity_id', $opportunity->id)
        ->where('milestone', 'day_3')
        ->count())->toBe(2);
});
