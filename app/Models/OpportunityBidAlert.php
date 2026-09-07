<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityBidAlert extends Model
{
    protected $fillable = [
        'opportunity_id',
        'milestone',
        'response_due',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'response_due' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
