<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Signals the live chat channel for one opportunity — fired regardless of
 * whether the comment came from the public board's modal or the Filament
 * admin panel's relation manager (see OpportunityBidCommentObserver).
 */
class BidCommentPosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly int $opportunityId) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("opportunity.{$this->opportunityId}.comments");
    }

    public function broadcastAs(): string
    {
        return 'BidCommentPosted';
    }
}
