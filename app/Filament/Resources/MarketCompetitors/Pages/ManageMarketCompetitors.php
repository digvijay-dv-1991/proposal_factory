<?php

namespace App\Filament\Resources\MarketCompetitors\Pages;

use App\Filament\Resources\MarketCompetitors\MarketCompetitorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMarketCompetitors extends ManageRecords
{
    protected static string $resource = MarketCompetitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
