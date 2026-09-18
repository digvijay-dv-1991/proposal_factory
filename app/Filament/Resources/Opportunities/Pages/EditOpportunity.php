<?php

namespace App\Filament\Resources\Opportunities\Pages;

use App\Filament\Resources\Opportunities\Actions\ChangeDecisionAction;
use App\Filament\Resources\Opportunities\OpportunityResource;
use App\Models\Opportunity;
use App\Services\OpportunityDecisionService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    /**
     * Captured in mutateFormDataBeforeSave(), before the record is updated,
     * so afterSave() can tell whether this save is what actually changed
     * the decision (and therefore needs a decision_history row) — see
     * OpportunityDecisionService's docblock for why that write doesn't
     * happen unconditionally on every save.
     */
    private ?string $previousDecision = null;

    protected function getHeaderActions(): array
    {
        return [
            ChangeDecisionAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record instanceof Opportunity) {
            $this->previousDecision = $this->record->decision;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record instanceof Opportunity) {
            return;
        }

        $decision = $this->record->decision;

        if ($decision === $this->previousDecision) {
            return;
        }

        $service = app(OpportunityDecisionService::class);

        if ($service->requiresReason($decision)) {
            $service->recordHistory($this->record);
        }
    }
}
