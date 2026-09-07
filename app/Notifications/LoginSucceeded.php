<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class LoginSucceeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Carbon $loggedInAt,
        private readonly string $ipAddress,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New sign-in to your ALQIMI Capture Deck account')
            ->line("Your account was signed into at {$this->loggedInAt->format('M j, Y g:i A')} from IP {$this->ipAddress}.")
            ->line("If this wasn't you, contact your administrator right away.");
    }
}
