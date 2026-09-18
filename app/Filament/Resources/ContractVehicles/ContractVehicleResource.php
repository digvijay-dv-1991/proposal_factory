<?php

namespace App\Filament\Resources\ContractVehicles;

use App\Filament\Resources\ContractVehicles\Pages\ManageContractVehicles;
use App\Filament\Resources\ContractVehicles\Schemas\ContractVehicleForm;
use App\Filament\Resources\ContractVehicles\Tables\ContractVehiclesTable;
use App\Models\ContractVehicle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContractVehicleResource extends Resource
{
    protected static ?string $model = ContractVehicle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Market Intelligence';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ContractVehicleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractVehiclesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContractVehicles::route('/'),
        ];
    }
}
