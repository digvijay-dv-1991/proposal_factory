<?php

namespace App\Filament\Resources\IndustryEvents\Tables;

use App\Filament\Resources\IndustryEvents\Schemas\IndustryEventForm;
use App\Models\IndustryEvent;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IndustryEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('semibold')
                    ->description(fn (IndustryEvent $record): string => $record->host)
                    ->searchable(['name', 'host']),
                TextColumn::make('starts_on')
                    ->label('Dates')
                    ->date('M j, Y')
                    ->formatStateUsing(fn (IndustryEvent $record): string => $record->starts_on->equalTo($record->ends_on)
                        ? $record->starts_on->format('M j, Y')
                        : $record->starts_on->format('M j').' – '.$record->ends_on->format('M j, Y'))
                    ->sortable(),
                TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('class_name')
                    ->label('Color')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => IndustryEventForm::CLASS_NAMES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'event-public' => 'success',
                        'event-industry' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('starts_on', 'desc')
            ->filters([
                SelectFilter::make('class_name')
                    ->label('Color')
                    ->options(IndustryEventForm::CLASS_NAMES),
            ])
            ->recordActions([
                Action::make('visit')
                    ->label('Visit')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (IndustryEvent $record): string => $record->url)
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
