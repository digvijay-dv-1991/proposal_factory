<?php

namespace App\Filament\Resources\Opportunities\Pages;

use App\Filament\Resources\Opportunities\OpportunityResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['external_id'] = 'OPP-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));

        return $data;
    }
}
