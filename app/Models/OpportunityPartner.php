<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityPartner extends Model
{
    protected $fillable = [
        'opportunity_id',
        'company',
        'role',
        'status',
        'contact_email',
        'contact_phone',
        'capability',
        'rationale',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
