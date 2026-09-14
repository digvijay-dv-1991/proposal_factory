<?php

namespace App\Filament\Resources\ScoringSettings;

use App\Filament\Resources\ScoringSettings\Pages\ManageScoringSettings;
use App\Filament\Resources\ScoringSettings\Schemas\ScoringSettingForm;
use App\Filament\Resources\ScoringSettings\Tables\ScoringSettingsTable;
use App\Models\ScoringSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ScoringSettingResource extends Resource
{
    protected static ?string $model = ScoringSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Bid Strength Scoring';

    public static function form(Schema $schema): Schema
    {
        return ScoringSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScoringSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageScoringSettings::route('/'),
        ];
    }
}
