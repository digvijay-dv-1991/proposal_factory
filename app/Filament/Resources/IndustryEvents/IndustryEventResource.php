<?php

namespace App\Filament\Resources\IndustryEvents;

use App\Filament\Resources\IndustryEvents\Pages\ManageIndustryEvents;
use App\Filament\Resources\IndustryEvents\Schemas\IndustryEventForm;
use App\Filament\Resources\IndustryEvents\Tables\IndustryEventsTable;
use App\Models\IndustryEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IndustryEventResource extends Resource
{
    protected static ?string $model = IndustryEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Market Intelligence';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return IndustryEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndustryEventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIndustryEvents::route('/'),
        ];
    }
}
