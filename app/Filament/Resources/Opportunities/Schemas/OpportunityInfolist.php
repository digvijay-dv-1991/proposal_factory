<?php

namespace App\Filament\Resources\Opportunities\Schemas;

use App\Filament\Resources\Opportunities\OpportunityDecisionColor;
use App\Models\Opportunity;
use App\Support\CompactNumber;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class OpportunityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Snapshot')
                    ->components([
                        Grid::make(['default' => 2, 'lg' => 4])
                            ->components([
                                TextEntry::make('decision')
                                    ->badge()
                                    ->size(TextSize::Large)
                                    ->color(fn (string $state): string => OpportunityDecisionColor::forDecision($state)),
                                TextEntry::make('fit')
                                    ->label('Fit')
                                    ->badge()
                                    ->size(TextSize::Large)
                                    ->color(fn (Opportunity $record): string => match ($record->fit) {
                                        'Strong' => 'success',
                                        'Moderate' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('value')
                                    ->label('Value')
                                    ->weight('bold')
                                    ->size(TextSize::Large)
                                    ->formatStateUsing(fn (Opportunity $record): string => CompactNumber::money((float) $record->value)),
                                TextEntry::make('probability')
                                    ->label('Win probability')
                                    ->weight('bold')
                                    ->size(TextSize::Large)
                                    ->suffix('%'),
                            ]),
                    ]),
                Section::make('Overview')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->columns(3)
                    ->components([
                        TextEntry::make('agency'),
                        TextEntry::make('agency_subsection')->label('Sub-agency / office')->placeholder('—'),
                        TextEntry::make('solicitation')->label('Solicitation #')->placeholder('—'),
                        TextEntry::make('phase')->badge()->color('gray'),
                        TextEntry::make('origin')->label('Section')->badge()->color('gray'),
                        TextEntry::make('response_due')
                            ->date()
                            ->color(fn (?Opportunity $record): ?string => $record?->response_due?->isPast() ? 'danger' : null)
                            ->placeholder('—'),
                        TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                    ]),
                Section::make('Gap Analysis')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->columns(2)
                    ->visible(fn (Opportunity $record): bool => $record->has_gap)
                    ->components([
                        TextEntry::make('gap_status')->badge()->color('gray'),
                        TextEntry::make('gap_owner')->placeholder('—'),
                        TextEntry::make('gap')->columnSpanFull(),
                        TextEntry::make('gap_mitigation')->label('Mitigation plan')->columnSpanFull()->placeholder('—'),
                    ]),
                Section::make('Competitive & Incumbent')
                    ->icon(Heroicon::OutlinedScale)
                    ->columns(2)
                    ->visible(fn (Opportunity $record): bool => $record->has_competitive_analysis)
                    ->components([
                        TextEntry::make('competitive_position')->badge()->color('gray'),
                        TextEntry::make('incumbent')->placeholder('Unknown'),
                        TextEntry::make('competitive_analysis')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('competitive_discriminators')->columnSpanFull()->placeholder('—'),
                    ]),
                Section::make('AI Insights')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->visible(fn (Opportunity $record): bool => filled($record->ai_analysis_generated_at))
                    ->components([
                        TextEntry::make('ai_executive_summary')->label('Executive summary')->columnSpanFull(),
                        TextEntry::make('ai_why_it_matters')->label('Why it matters')->columnSpanFull(),
                    ]),
            ]);
    }
}
