<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketCompetitor extends Model
{
    protected $fillable = [
        'name',
        'url',
        'label',
        'alqimi_products',
        'competitor_offering',
        'overlap',
        'alqimi_advantage',
        'competitor_advantage',
        'strategy',
    ];
}
