<?php

namespace App\Support;

/**
 * Shared compact formatter for money shown in a small, fixed-width space
 * (stat tiles, KPI cards) where a full "$2,208,925,000.00" would overflow
 * or force an ugly wrap — used by both the admin dashboard's stat widgets
 * and the Opportunity view page's summary tiles.
 */
class CompactNumber
{
    public static function money(float $value): string
    {
        return match (true) {
            $value >= 1_000_000_000 => '$'.number_format($value / 1_000_000_000, 2).'B',
            $value >= 1_000_000 => '$'.number_format($value / 1_000_000, 2).'M',
            $value >= 1_000 => '$'.number_format($value / 1_000, 1).'K',
            default => '$'.number_format($value),
        };
    }
}
