<?php

namespace App\Filament\Resources\MarketPartners\Pages;

use App\Filament\Resources\MarketPartners\MarketPartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMarketPartners extends ManageRecords
{
    protected static string $resource = MarketPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
