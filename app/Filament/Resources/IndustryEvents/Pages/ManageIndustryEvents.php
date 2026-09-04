<?php

namespace App\Filament\Resources\IndustryEvents\Pages;

use App\Filament\Resources\IndustryEvents\IndustryEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIndustryEvents extends ManageRecords
{
    protected static string $resource = IndustryEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
