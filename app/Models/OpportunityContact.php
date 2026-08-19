<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityContact extends Model
{
    protected $fillable = [
        'opportunity_id',
        'name',
        'title',
        'organization',
        'role',
        'email',
        'phone',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
