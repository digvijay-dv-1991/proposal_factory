<?php

namespace App\Observers;

use App\Events\BidCommentPosted;
use App\Models\OpportunityBidComment;

class OpportunityBidCommentObserver
{
    /**
     * Signals the live chat channel for this opportunity — regardless of
     * whether the comment came from the public board's modal or the
     * Filament admin panel's relation manager.
     */
    public function created(OpportunityBidComment $comment): void
    {
        BidCommentPosted::dispatch($comment->opportunity_id);
    }
}
