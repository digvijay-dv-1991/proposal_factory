<?php

namespace App\Filament\Resources\ContractVehicles\Pages;

use App\Filament\Resources\ContractVehicles\ContractVehicleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageContractVehicles extends ManageRecords
{
    protected static string $resource = ContractVehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
