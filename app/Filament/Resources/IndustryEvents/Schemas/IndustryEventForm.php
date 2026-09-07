<?php

namespace App\Filament\Resources\IndustryEvents\Schemas;

use App\Models\IndustryEvent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class IndustryEventForm
{
    /**
     * Fixed set — drives the calendar's border/background color in
     * capture-deck.css (.calendar-event.event-*), so unlike the other
     * free-text classification fields this one is a real closed list.
     *
     * @var array<string, string>
     */
    public const CLASS_NAMES = [
        'event-defense' => 'Defense & Federal',
        'event-public' => 'Public Sector',
        'event-industry' => 'Industry / Vendor',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Event')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('external_id')
                            ->label('Reference ID')
                            ->helperText('Short unique code, e.g. EVT-INSS26.')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('host')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('starts_on')
                            ->required()
                            ->native(false),
                        DatePicker::make('ends_on')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('starts_on'),
                        TextInput::make('location')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('type')
                            ->helperText('Free-text category, e.g. "State & Local".')
                            ->datalist(fn () => self::distinctValues('type'))
                            ->required()
                            ->maxLength(255),
                        Select::make('class_name')
                            ->label('Calendar color')
                            ->options(self::CLASS_NAMES)
                            ->required()
                            ->native(false),
                    ]),
                Section::make('Details')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->components([
                        Textarea::make('summary')
                            ->required()
                            ->autosize()
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->label('Event URL')
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
        return IndustryEvent::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
