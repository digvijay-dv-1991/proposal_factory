<?php

namespace App\Services;

use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityBidAlert;
use App\Models\User;
use App\Notifications\BidDueDateReminder;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class BidAlertService
{
    /**
     * Day-based reminder checkpoints, matched against the number of days
     * remaining until Opportunity::response_due.
     *
     * @var array<string, int>
     */
    private const DAY_MILESTONES = [
        'day_14' => 14,
        'day_7' => 7,
        'day_3' => 3,
        'day_1' => 1,
        'due_day' => 0,
    ];

    /**
     * Client's business timezone — drives both the daily schedule time (see
     * routes/console.php) and "today" for the day-based comparisons below.
     */
    public const TIMEZONE = 'America/New_York';

    public function sendMovedToBidAlert(Opportunity $opportunity): void
    {
        $this->send($opportunity, 'moved_to_bid');
    }

    /**
     * Entry point for the daily scheduled command. For every opportunity
     * currently marked Bid with a response due date, sends whichever
     * day-based milestone (14/7/3/1/0 days out) matches today and hasn't
     * already been sent for the opportunity's current response_due — an
     * edited due date changes that value, so it naturally becomes eligible
     * to fire again without any explicit "reschedule" step.
     */
    public function sendDueDateReminders(): void
    {
        $today = Carbon::today(self::TIMEZONE)->startOfDay();

        Opportunity::query()
            ->where('decision', 'Bid')
            ->whereNotNull('response_due')
            ->get()
            ->each(function (Opportunity $opportunity) use ($today): void {
                // response_due is a plain DATE column — Eloquent's date cast
                // parses it in the app's default (UTC) timezone, so comparing
                // its timestamp directly against $today (anchored to the
                // client's timezone) would be off by that UTC offset. Re-read
                // it as a calendar date in the client's timezone instead.
                $due = Carbon::parse($opportunity->response_due->format('Y-m-d'), self::TIMEZONE)->startOfDay();
                $daysRemaining = intdiv($due->getTimestamp() - $today->getTimestamp(), 86400);

                $milestone = array_search($daysRemaining, self::DAY_MILESTONES, true);

                if ($milestone !== false) {
                    $this->send($opportunity, $milestone);
                }
            });
    }

    private function send(Opportunity $opportunity, string $milestone): void
    {
        $alreadySent = OpportunityBidAlert::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('milestone', $milestone)
            ->where('response_due', $opportunity->response_due)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $recipients = $this->recipients();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new BidDueDateReminder($opportunity, $milestone));

            FilamentNotification::make()
                ->title($this->notificationTitle($opportunity, $milestone))
                ->body($this->notificationBody($opportunity, $milestone))
                ->color($this->notificationColor($milestone))
                ->icon(Heroicon::OutlinedFlag)
                ->actions([
                    Action::make('view')
                        ->label('Open opportunity')
                        ->url(OpportunityResource::getUrl('view', ['record' => $opportunity->id])),
                ])
                ->sendToDatabase($recipients, isEventDispatched: true);
        }

        OpportunityBidAlert::create([
            'opportunity_id' => $opportunity->id,
            'milestone' => $milestone,
            'response_due' => $opportunity->response_due,
            'sent_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients(): Collection
    {
        return User::role('Admin')->get();
    }

    private function notificationTitle(Opportunity $opportunity, string $milestone): string
    {
        return match ($milestone) {
            'moved_to_bid' => "Bid decision made: {$opportunity->name}",
            'due_day' => "Due today: {$opportunity->name}",
            default => self::DAY_MILESTONES[$milestone]."-day reminder: {$opportunity->name}",
        };
    }

    private function notificationBody(Opportunity $opportunity, string $milestone): string
    {
        return match ($milestone) {
            'moved_to_bid' => "{$opportunity->agency} — just moved into Bid.",
            'due_day' => "{$opportunity->agency} — the response is due today.",
            default => "{$opportunity->agency} — the response is due in ".self::DAY_MILESTONES[$milestone].' day'
                .(self::DAY_MILESTONES[$milestone] === 1 ? '' : 's').'.',
        };
    }

    private function notificationColor(string $milestone): string
    {
        return match ($milestone) {
            'moved_to_bid' => 'success',
            'due_day', 'day_1' => 'danger',
            default => 'warning',
        };
    }
}
