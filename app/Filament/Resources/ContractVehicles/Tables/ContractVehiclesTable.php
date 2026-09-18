<?php

namespace App\Filament\Resources\ContractVehicles\Tables;

use App\Models\ContractVehicle;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractVehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Vehicle')
                    ->weight('semibold')
                    ->description(fn (ContractVehicle $record): string => $record->full_name)
                    ->searchable(['name', 'full_name']),
                TextColumn::make('agency')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => self::statusColor($state))
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('visit')
                    ->label('Visit')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (ContractVehicle $record): string => $record->url)
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

    private static function statusColor(string $status): string
    {
        return match (true) {
            str_contains($status, 'ALQIMI') => 'success',
            str_contains($status, 'Expiring') => 'warning',
            str_contains($status, 'Expired'), str_contains($status, 'Inactive') => 'danger',
            default => 'gray',
        };
    }
}
