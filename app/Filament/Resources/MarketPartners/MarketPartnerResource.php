<?php

namespace App\Filament\Resources\MarketPartners;

use App\Filament\Resources\MarketPartners\Pages\ManageMarketPartners;
use App\Filament\Resources\MarketPartners\Schemas\MarketPartnerForm;
use App\Filament\Resources\MarketPartners\Tables\MarketPartnersTable;
use App\Models\MarketPartner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MarketPartnerResource extends Resource
{
    protected static ?string $model = MarketPartner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Market Intelligence';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return MarketPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketPartnersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMarketPartners::route('/'),
        ];
    }
}
