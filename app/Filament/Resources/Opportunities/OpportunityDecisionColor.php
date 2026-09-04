<?php

namespace App\Filament\Resources\Opportunities;

/**
 * Shared decision => badge-color mapping, reused across the table, infolist,
 * and the quick "Change Decision" action so the palette can't drift between
 * them.
 */
class OpportunityDecisionColor
{
    public static function forDecision(string $decision): string
    {
        return match ($decision) {
            'Bid' => 'success',
            'No Bid' => 'danger',
            'Shape' => 'primary',
            'More Info' => 'info',
            'Monitoring' => 'warning',
            default => 'gray',
        };
    }
}
