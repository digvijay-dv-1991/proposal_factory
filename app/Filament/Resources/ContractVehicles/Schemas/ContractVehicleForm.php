<?php

namespace App\Filament\Resources\ContractVehicles\Schemas;

use App\Models\ContractVehicle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ContractVehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Vehicle')
                    ->description('The core identity of this contract vehicle — how it shows up across the app.')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Short name')
                            ->helperText('e.g. "OASIS+" — shown on opportunity cards.')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('full_name')
                            ->label('Full name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('agency')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('type')
                            ->datalist(fn () => self::distinctValues('type'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('status')
                            ->datalist(fn () => self::distinctValues('status'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Details')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->components([
                        Textarea::make('description')
                            ->required()
                            ->autosize()
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('alqimi_use')
                            ->label('ALQIMI use')
                            ->helperText('How ALQIMI actually uses this vehicle today, if at all.')
                            ->autosize()
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->label('Reference URL')
                            ->url()
                            ->required()
                            ->maxLength(2048)
                            ->prefixIcon(Heroicon::OutlinedLink)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function distinctValues(string $column): array
    {
        return ContractVehicle::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
