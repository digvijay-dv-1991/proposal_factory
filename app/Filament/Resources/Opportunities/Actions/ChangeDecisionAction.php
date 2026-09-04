<?php

namespace App\Filament\Resources\Opportunities\Actions;

use App\Filament\Resources\Opportunities\Schemas\OpportunityForm;
use App\Models\Opportunity;
use App\Services\OpportunityDecisionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * One-click Bid/No Bid (or any decision) call from the table row or the
 * view page header — no need to open the full edit form and find the
 * Decision tab. Delegates the actual who/reason rule + history write to
 * OpportunityDecisionService so this stays presentation-only.
 */
class ChangeDecisionAction
{
    public static function make(): Action
    {
        return Action::make('changeDecision')
            ->label('Change Decision')
            ->icon(Heroicon::OutlinedFlag)
            ->color('primary')
            ->modalHeading('Change decision')
            ->modalSubmitActionLabel('Save decision')
            ->fillForm(fn (Opportunity $record): array => [
                'decision' => $record->decision,
                'bid_priority' => $record->bid_priority !== null ? (string) $record->bid_priority : null,
                'decision_by' => $record->decision_by,
                'decision_comment' => $record->decision_comment,
            ])
            ->schema([
                Select::make('decision')
                    ->options(OpportunityForm::DECISIONS)
                    ->required()
                    ->live()
                    ->native(false),
                Select::make('bid_priority')
                    ->label('Bid priority')
                    ->options(OpportunityForm::BID_PRIORITIES)
                    ->native(false)
                    ->visible(fn (Get $get): bool => $get('decision') === 'Bid')
                    ->required(fn (Get $get): bool => $get('decision') === 'Bid'),
                TextInput::make('decision_by')
                    ->label('Decided by')
                    ->maxLength(255)
                    ->visible(fn (Get $get, OpportunityDecisionService $service): bool => $service->requiresReason($get('decision')))
                    ->required(fn (Get $get, OpportunityDecisionService $service): bool => $service->requiresReason($get('decision'))),
                Textarea::make('decision_comment')
                    ->label('Reason')
                    ->rows(3)
                    ->visible(fn (Get $get, OpportunityDecisionService $service): bool => $service->requiresReason($get('decision')))
                    ->required(fn (Get $get, OpportunityDecisionService $service): bool => $service->requiresReason($get('decision'))),
            ])
            ->action(function (array $data, Opportunity $record, OpportunityDecisionService $service): void {
                $service->apply(
                    $record,
                    $data['decision'],
                    $data['decision_by'] ?? null,
                    $data['decision_comment'] ?? null,
                    isset($data['bid_priority']) ? (int) $data['bid_priority'] : null,
                );

                Notification::make()
                    ->title('Decision updated')
                    ->success()
                    ->send();
            });
    }
}
