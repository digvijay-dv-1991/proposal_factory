<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityUpdate extends Model
{
    protected $fillable = [
        'opportunity_id',
        'date',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
