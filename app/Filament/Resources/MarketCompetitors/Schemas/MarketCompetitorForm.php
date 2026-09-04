<?php

namespace App\Filament\Resources\MarketCompetitors\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MarketCompetitorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Competitor')
                    ->icon(Heroicon::OutlinedFlag)
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('url')
                            ->label('Website')
                            ->url()
                            ->required()
                            ->maxLength(2048)
                            ->prefixIcon(Heroicon::OutlinedLink),
                        TextInput::make('label')
                            ->helperText('Short positioning line shown under the name, e.g. "Data Platform · AI · Defense".')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Positioning')
                    ->description('What each side offers and how ALQIMI should play against this competitor.')
                    ->icon(Heroicon::OutlinedScale)
                    ->columns(2)
                    ->components([
                        Textarea::make('alqimi_products')
                            ->label('ALQIMI products')
                            ->required()
                            ->rows(3),
                        Textarea::make('competitor_offering')
                            ->label('Their offering')
                            ->required()
                            ->rows(3),
                        Textarea::make('overlap')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('alqimi_advantage')
                            ->label('Our advantage')
                            ->required()
                            ->rows(3),
                        Textarea::make('competitor_advantage')
                            ->label('Their advantage')
                            ->required()
                            ->rows(3),
                        Textarea::make('strategy')
                            ->label('Play against them')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
