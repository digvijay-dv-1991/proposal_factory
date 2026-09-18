<?php

namespace App\Filament\Resources\MarketCompetitors;

use App\Filament\Resources\MarketCompetitors\Pages\ManageMarketCompetitors;
use App\Filament\Resources\MarketCompetitors\Schemas\MarketCompetitorForm;
use App\Filament\Resources\MarketCompetitors\Tables\MarketCompetitorsTable;
use App\Models\MarketCompetitor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MarketCompetitorResource extends Resource
{
    protected static ?string $model = MarketCompetitor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Market Intelligence';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MarketCompetitorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketCompetitorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMarketCompetitors::route('/'),
        ];
    }
}
