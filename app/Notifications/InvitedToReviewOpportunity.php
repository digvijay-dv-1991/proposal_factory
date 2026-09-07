<?php

namespace App\Notifications;

use App\Models\Opportunity;
use Illuminate\Notifications\Notification;

class InvitedToReviewOpportunity extends Notification
{
    public function __construct(
        private readonly Opportunity $opportunity,
        private readonly string $invitedByName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'opportunity_id' => $this->opportunity->id,
            'opportunity_name' => $this->opportunity->name,
            'invited_by' => $this->invitedByName,
        ];
    }
}
