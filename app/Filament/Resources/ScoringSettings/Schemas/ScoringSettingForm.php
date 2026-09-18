<?php

namespace App\Filament\Resources\ScoringSettings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ScoringSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Bid Strength (win %) weights')
                    ->description('How much each factor counts toward an AI-drafted Bid Strength score. Capability Fit — can ALQIMI actually do this work — should carry the most weight; the rest are secondary. These should add up to 100.')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->columns(2)
                    ->components([
                        TextInput::make('capability_fit_weight')
                            ->label('Capability Fit')
                            ->helperText('Can ALQIMI actually deliver this work — the dominant factor.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('competitive_position_weight')
                            ->label('Competitive Position')
                            ->helperText('Incumbent presence and competition level.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('mission_fit_weight')
                            ->label('Mission Fit')
                            ->helperText('Alignment with ALQIMI\'s focus areas.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('timing_weight')
                            ->label('Timing / Stage')
                            ->helperText('Runway before response is due, how early-stage the notice is.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('vehicle_accessibility_weight')
                            ->label('Vehicle Accessibility')
                            ->helperText('How reachable the contract vehicle/set-aside is for ALQIMI.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ]),
            ]);
    }
}
