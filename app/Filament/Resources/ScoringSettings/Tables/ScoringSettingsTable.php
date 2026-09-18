<?php

namespace App\Filament\Resources\ScoringSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScoringSettingsTable
{
    /**
     * A single settings row, not a list — no create/delete actions, since
     * this table is seeded with exactly one record.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('capability_fit_weight')->label('Capability Fit')->suffix('%'),
                TextColumn::make('competitive_position_weight')->label('Competitive Position')->suffix('%'),
                TextColumn::make('mission_fit_weight')->label('Mission Fit')->suffix('%'),
                TextColumn::make('timing_weight')->label('Timing / Stage')->suffix('%'),
                TextColumn::make('vehicle_accessibility_weight')->label('Vehicle Accessibility')->suffix('%'),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth(Width::Large),
            ]);
    }
}
