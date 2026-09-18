<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
class OpportunityBidComment extends Model
{
    protected $fillable = [
        'opportunity_id',
        'user_id',
        'author_name',
        'text',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The name shown next to a comment: the posting user's name, or the
     * guest name they typed if they weren't logged in.
     */
    public function displayName(): string
    {
        $user = $this->user;

        return $user !== null ? $user->name : ($this->author_name ?: 'Guest');
    }
}
