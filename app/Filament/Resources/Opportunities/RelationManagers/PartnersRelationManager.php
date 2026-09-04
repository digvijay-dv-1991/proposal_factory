<?php

namespace App\Filament\Resources\Opportunities\RelationManagers;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class PartnersRelationManager extends RelationManager
{
    protected static string $relationship = 'partners';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Teaming';
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedUserGroup;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('company')->required()->maxLength(255),
                TextInput::make('role')->maxLength(255),
                TextInput::make('status')->maxLength(255),
                TextInput::make('capability')->columnSpanFull(),
                Textarea::make('rationale')->rows(3)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company')
            ->columns([
                TextColumn::make('company')->weight('semibold')->searchable(),
                TextColumn::make('role')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('status')->badge()->color('info')->placeholder('—'),
                TextColumn::make('capability')->limit(50)->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->slideOver(),
            ])
            ->recordActions([
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
