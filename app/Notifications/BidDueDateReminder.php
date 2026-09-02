<?php

namespace App\Notifications;

use App\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class BidDueDateReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'moved_to_bid'|'day_14'|'day_7'|'day_3'|'day_1'|'due_day'  $milestone
     */
    public function __construct(
        private readonly Opportunity $opportunity,
        private readonly string $milestone,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $due = $this->formattedDueDate();
        $url = Route::has('opportunities.index')
            ? route('opportunities.index', ['opportunity' => $this->opportunity->id])
            : url('/opportunities');

        return (new MailMessage)
            ->subject($this->subject())
            ->greeting($this->headline())
            ->line("Opportunity: {$this->opportunity->name}")
            ->line("Agency: {$this->opportunity->agency}")
            ->line("Due: {$due}")
            ->action('Open in Capture Deck', $url)
            ->line('This is an automated reminder from the ALQIMI Capture Deck bid pipeline.');
    }

    private function subject(): string
    {
        return match ($this->milestone) {
            'moved_to_bid' => "Bid decision made: {$this->opportunity->name}",
            'due_day' => "Due today: {$this->opportunity->name}",
            default => "{$this->daysRemaining()}-day reminder: {$this->opportunity->name}",
        };
    }

    private function headline(): string
    {
        return match ($this->milestone) {
            'moved_to_bid' => 'This opportunity was just moved into Bid.',
            'due_day' => 'The response is due today.',
            default => "The response is due in {$this->daysRemaining()} day".($this->daysRemaining() === 1 ? '' : 's').'.',
        };
    }

    private function daysRemaining(): int
    {
        return match ($this->milestone) {
            'day_14' => 14,
            'day_7' => 7,
            'day_3' => 3,
            'day_1' => 1,
            default => 0,
        };
    }

    private function formattedDueDate(): string
    {
        $date = $this->opportunity->response_due?->format('l, F j, Y') ?? 'Not set';
        $time = trim((string) $this->opportunity->response_time);

        return $time === '' ? $date : "{$date} at {$time}";
    }
}
