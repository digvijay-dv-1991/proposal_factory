<?php

namespace App\Filament\Resources\Opportunities\Pages;

use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Filament\Resources\Opportunities\Widgets\OpportunityListStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Carries its own operational KPI row (OpportunityListStatsOverview) rather
 * than the portfolio-level dashboard widgets — see that widget's docblock
 * for why they're intentionally different, not a repeat of the dashboard.
 */
class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OpportunityListStatsOverview::class,
        ];
    }
}
