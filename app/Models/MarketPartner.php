<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPartner extends Model
{
    protected $fillable = [
        'name',
        'url',
        'label',
        'what_they_do',
        'client_alignment',
        'product_areas',
        'partnership_value',
        'use_together',
    ];
}
