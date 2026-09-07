<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 */
class IndustryEvent extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'starts_on',
        'ends_on',
        'location',
        'host',
        'type',
        'class_name',
        'summary',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
