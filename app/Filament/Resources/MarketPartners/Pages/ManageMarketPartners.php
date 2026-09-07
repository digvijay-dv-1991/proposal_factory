<?php

namespace App\Filament\Resources\MarketPartners\Pages;

use App\Filament\Resources\MarketPartners\MarketPartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageMarketPartners extends ManageRecords
{
    protected static string $resource = MarketPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth(Width::FiveExtraLarge),
        ];
    }
}
