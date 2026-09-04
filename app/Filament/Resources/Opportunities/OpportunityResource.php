<?php

namespace App\Filament\Resources\Opportunities;

use App\Filament\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Filament\Resources\Opportunities\Pages\ViewOpportunity;
use App\Filament\Resources\Opportunities\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Opportunities\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\Opportunities\RelationManagers\PartnersRelationManager;
use App\Filament\Resources\Opportunities\RelationManagers\UpdatesRelationManager;
use App\Filament\Resources\Opportunities\Schemas\OpportunityForm;
use App\Filament\Resources\Opportunities\Schemas\OpportunityInfolist;
use App\Filament\Resources\Opportunities\Tables\OpportunitiesTable;
use App\Models\Opportunity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Capture Management';

    protected static ?int $navigationSort = 0;

    /**
     * @var array<int, string>
     */
    protected static array $globallySearchableAttributes = ['name', 'agency', 'solicitation', 'naics'];

    public static function form(Schema $schema): Schema
    {
        return OpportunityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OpportunityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpportunitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ContactsRelationManager::class,
            PartnersRelationManager::class,
            UpdatesRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpportunities::route('/'),
            'create' => CreateOpportunity::route('/create'),
            'view' => ViewOpportunity::route('/{record}'),
            'edit' => EditOpportunity::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->where('decision', 'Pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
