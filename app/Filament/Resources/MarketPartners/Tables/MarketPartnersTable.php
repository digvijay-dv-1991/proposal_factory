<?php

namespace App\Filament\Resources\MarketPartners\Tables;

use App\Models\MarketPartner;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketPartnersTable
{
    /**
     * Cards, not spreadsheet rows — same rationale as MarketCompetitorsTable.
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
                TextColumn::make('partnership_value')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->iconColor('primary')
                    ->limit(90)
                    ->tooltip(fn (MarketPartner $record): string => $record->partnership_value)
                    ->columnSpanFull(),
                TextColumn::make('use_together')
                    ->label('How we use them together')
                    ->icon(Heroicon::OutlinedPuzzlePiece)
                    ->iconColor('success')
                    ->limit(90)
                    ->tooltip(fn (MarketPartner $record): string => $record->use_together)
                    ->columnSpanFull(),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('visit')
                    ->label('Visit')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (MarketPartner $record): string => $record->url)
                    ->openUrlInNewTab(),
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
