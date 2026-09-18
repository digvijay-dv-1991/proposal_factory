<?php

namespace App\Filament\Resources\Opportunities\Tables;

use App\Filament\Resources\Opportunities\Actions\ChangeDecisionAction;
use App\Filament\Resources\Opportunities\OpportunityDecisionColor;
use App\Filament\Resources\Opportunities\Schemas\OpportunityForm;
use App\Livewire\OpportunityBoard;
use App\Models\Opportunity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class OpportunitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('semibold')
                    ->limit(45)
                    ->tooltip(fn (Opportunity $record): ?string => strlen($record->name) > 45 ? $record->name : null)
                    ->description(fn (Opportunity $record): string => Str::limit($record->agency, 40))
                    ->searchable(['name', 'agency', 'solicitation', 'naics']),
                TextColumn::make('decision')
                    ->badge()
                    ->color(fn (string $state): string => OpportunityDecisionColor::forDecision($state)),
                TextColumn::make('phase')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('value')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('probability')
                    ->label('P(win)')
                    ->suffix('%')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('response_due')
                    ->label('Response due')
                    ->date()
                    ->sortable()
                    ->color(fn (?Opportunity $record): ?string => $record?->response_due?->isPast() ? 'danger' : null)
                    ->placeholder('—'),
                TextColumn::make('alqimi_sme')
                    ->label('SME')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->placeholder('Unassigned')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fit')
                    ->badge()
                    ->color(fn (Opportunity $record): string => match ($record->fit) {
                        'Strong' => 'success',
                        'Moderate' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->groups(['phase', 'decision', 'origin'])
            ->searchable()
            ->filters([
                SelectFilter::make('decision')
                    ->options(OpportunityForm::DECISIONS)
                    ->multiple(),
                SelectFilter::make('phase')
                    ->options(array_combine(OpportunityBoard::PHASES, OpportunityBoard::PHASES))
                    ->multiple(),
                SelectFilter::make('origin')
                    ->label('Section')
                    ->options(array_combine(OpportunityBoard::SECTIONS, OpportunityBoard::SECTIONS))
                    ->multiple(),
                Filter::make('response_overdue')
                    ->label('Response overdue')
                    ->query(fn (Builder $query): Builder => $query->whereDate('response_due', '<', now())),
            ])
            ->recordActions([
                ViewAction::make(),
                ChangeDecisionAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
