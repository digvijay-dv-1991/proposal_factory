<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityEvidence extends Model
{
    protected $table = 'opportunity_evidence';

    protected $fillable = [
        'opportunity_id',
        'statement',
        'source',
        'confidence',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
