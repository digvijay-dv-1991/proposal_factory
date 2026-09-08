<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
class Suggestion extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'image_path',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SuggestionUpvote, $this>
     */
    public function upvotes(): HasMany
    {
        return $this->hasMany(SuggestionUpvote::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path !== null ? Storage::disk('public')->url($this->image_path) : null;
    }
}
