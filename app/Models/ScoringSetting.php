<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings: the Bid Strength (win %) rubric weights, editable by
 * an admin without a deploy. See the AI Analysis prompt, which reads
 * current() and asks the model to reason in these proportions.
 */
class ScoringSetting extends Model
{
    protected $fillable = [
        'capability_fit_weight',
        'competitive_position_weight',
        'mission_fit_weight',
        'timing_weight',
        'vehicle_accessibility_weight',
    ];

    protected function casts(): array
    {
        return [
            'capability_fit_weight' => 'integer',
            'competitive_position_weight' => 'integer',
            'mission_fit_weight' => 'integer',
            'timing_weight' => 'integer',
            'vehicle_accessibility_weight' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }
}
