<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A signal that something changed on the opportunity board — a decision, a
 * phase, or a brand-new opportunity. Carries no payload on purpose:
 * OpportunityBoard already re-queries its own filtered/tabbed data on every
 * render, so listeners just need to know "refresh", not what changed.
 */
class OpportunityBoardChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('opportunities-board');
    }

    public function broadcastAs(): string
    {
        return 'OpportunityBoardChanged';
    }
}
