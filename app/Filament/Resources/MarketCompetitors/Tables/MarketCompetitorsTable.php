<?php

namespace App\Filament\Resources\MarketCompetitors\Tables;

use App\Models\MarketCompetitor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketCompetitorsTable
{
    /**
     * Cards, not spreadsheet rows — competitor profiles are read for their
     * positioning story, not scanned column-by-column, so a content grid
     * (Filament's built-in card layout) reads far better than a wide table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->size(TextSize::Large)
                    ->searchable(),
                TextColumn::make('label')
                    ->color('gray')
                    ->searchable()
                    ->columnSpanFull(),
                TextColumn::make('alqimi_advantage')
                    ->label('Our advantage')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->iconColor('success')
                    ->limit(90)
                    ->tooltip(fn (MarketCompetitor $record): string => $record->alqimi_advantage)
                    ->columnSpanFull(),
                TextColumn::make('strategy')
                    ->label('Play against them')
                    ->icon(Heroicon::OutlinedFlag)
                    ->iconColor('warning')
                    ->limit(90)
                    ->tooltip(fn (MarketCompetitor $record): string => $record->strategy)
                    ->columnSpanFull(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('visit')
                    ->label('Visit')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (MarketCompetitor $record): string => $record->url)
                    ->openUrlInNewTab(),
                EditAction::make()->slideOver()->modalWidth(Width::FiveExtraLarge),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
