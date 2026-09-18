<?php

namespace App\Filament\Resources\Opportunities\RelationManagers;

use App\Models\OpportunityAttachment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Upload-and-remove only — attachments are never edited in place on the
 * public board either (OpportunityModal has uploadAttachment()/
 * removeAttachment(), no update path), so no EditAction is offered here.
 */
class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    private const ATTACHMENT_MIME_TYPES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg';

    private const ATTACHMENT_MAX_KB = 10240;

    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedPaperClip;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('File')
                    ->disk('public')
                    ->directory('opportunity-attachments')
                    ->preserveFilenames()
                    ->acceptedFileTypes(array_map(
                        fn (string $extension): string => '.'.$extension,
                        explode(',', self::ATTACHMENT_MIME_TYPES),
                    ))
                    ->maxSize(self::ATTACHMENT_MAX_KB)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->searchable(),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? number_format($state / 1024, 1).' KB' : '—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data): array {
                        $path = $data['path'];

                        $data['original_name'] = basename((string) $path);
                        $data['mime_type'] = Storage::disk('public')->mimeType($path);
                        $data['size'] = Storage::disk('public')->size($path);
                        $data['type'] = strtoupper(pathinfo((string) $path, PATHINFO_EXTENSION)).' attachment';

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->url(fn (OpportunityAttachment $record): string => Storage::disk('public')->url($record->path))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->before(function (OpportunityAttachment $record): void {
                        if ($record->path !== null) {
                            Storage::disk('public')->delete($record->path);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
