<?php

namespace App\Filament\Resources\Opportunities\RelationManagers;

use App\Models\OpportunityBidComment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * Mirrors the Bid / No Bid Discussion chat on the opportunity board's
 * Overview tab (OpportunityModal's bidComments) — same data, read/post
 * only here too, since the front end has no edit path for a comment
 * either.
 */
class BidCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'bidComments';

    protected static ?string $title = 'Bid / No Bid Discussion';

    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedChatBubbleLeftRight;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('text')
                    ->label('Comment')
                    ->placeholder('Should we bid on this? Share your take...')
                    ->rows(3)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('displayName')
                    ->label('Name')
                    ->getStateUsing(fn (OpportunityBidComment $record): string => $record->displayName()),
                TextColumn::make('text')->wrap(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
