<?php

namespace App\Filament\Resources\ScoringSettings\Pages;

use App\Filament\Resources\ScoringSettings\ScoringSettingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageScoringSettings extends ManageRecords
{
    protected static string $resource = ScoringSettingResource::class;

    /**
     * No create action — the table is seeded with exactly one settings row.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
