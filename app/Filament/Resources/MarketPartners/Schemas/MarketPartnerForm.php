<?php

namespace App\Filament\Resources\MarketPartners\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MarketPartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Partner')
                    ->icon(Heroicon::OutlinedUserGroup)
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
                            ->helperText('Short positioning line shown under the name.')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Partnership')
                    ->description('What they bring, how it fits our clients, and where teaming makes sense.')
                    ->icon(Heroicon::OutlinedPuzzlePiece)
                    ->columns(2)
                    ->components([
                        Textarea::make('what_they_do')
                            ->label('What they do')
                            ->required()
                            ->rows(3),
                        Textarea::make('client_alignment')
                            ->label('Client alignment')
                            ->required()
                            ->rows(3),
                        Textarea::make('product_areas')
                            ->label('Product areas')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('partnership_value')
                            ->label('Partnership value')
                            ->required()
                            ->rows(3),
                        Textarea::make('use_together')
                            ->label('How we use them together')
                            ->required()
                            ->rows(3),
                    ]),
            ]);
    }
}
