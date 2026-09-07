<?php

namespace App\Filament\Resources\Opportunities\Schemas;

use App\Livewire\OpportunityBoard;
use App\Livewire\OpportunityModal;
use App\Models\ContractVehicle;
use App\Models\Opportunity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class OpportunityForm
{
    /**
     * All 6 values the `decision` DB enum actually holds — matches
     * OpportunityModal::DECISIONS on the public board.
     *
     * @var array<string, string>
     */
    public const DECISIONS = [
        'Pending' => 'Pending',
        'More Info' => 'More Info',
        'Monitoring' => 'Monitoring',
        'Shape' => 'Shape',
        'Bid' => 'Bid',
        'No Bid' => 'No Bid',
    ];

    private const DECISIONS_REQUIRING_REASON = ['Bid', 'No Bid'];

    /**
     * @var array<int, string>
     */
    public const BID_PRIORITIES = [
        '1' => 'P1 — Top priority',
        '2' => 'P2 — Priority',
        '3' => 'P3 — Lower priority',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Opportunity')
                    ->persistTab()
                    ->columnSpanFull()
                    ->tabs([
                        self::overviewTab(),
                        self::decisionTab(),
                        self::captureTab(),
                        self::gapAnalysisTab(),
                        self::competitiveTab(),
                        self::rfpTab(),
                        self::aiInsightsTab(),
                    ]),
            ]);
    }

    private static function overviewTab(): Tab
    {
        return Tab::make('Overview')
            ->icon(Heroicon::OutlinedBriefcase)
            ->columns(1)
            ->components([
                Section::make('Identity')
                    ->columns(3)
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(3),
                        TextInput::make('agency')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('agency_subsection')
                            ->label('Sub-agency / office')
                            ->maxLength(255),
                        TextInput::make('solicitation')
                            ->label('Solicitation #')
                            ->maxLength(255),
                        TextInput::make('naics')
                            ->label('NAICS'),
                        TextInput::make('psc')
                            ->label('PSC'),
                        TextInput::make('value')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->required(),
                    ]),
                Section::make('Classification')
                    ->columns(3)
                    ->components([
                        Select::make('phase')
                            ->options(array_combine(OpportunityBoard::PHASES, OpportunityBoard::PHASES))
                            ->default('Pre-Solicitation')
                            ->required()
                            ->native(false),
                        Select::make('origin')
                            ->label('Section')
                            ->options(array_combine(OpportunityBoard::SECTIONS, OpportunityBoard::SECTIONS))
                            ->default('General')
                            ->required()
                            ->native(false),
                        TextInput::make('vehicle')
                            ->datalist(fn () => ContractVehicle::query()->orderBy('name')->pluck('name')->all()),
                        Select::make('set_aside')
                            ->options(fn (?Opportunity $record): array => self::setAsideOptions($record))
                            ->searchable()
                            ->native(false)
                            ->columnSpan(2),
                    ]),
                Section::make('Dates & Links')
                    ->columns(3)
                    ->components([
                        DatePicker::make('date_added')->default(now())->native(false),
                        DatePicker::make('response_due')->native(false),
                        TextInput::make('link')
                            ->label('Official source URL')
                            ->url()
                            ->prefixIcon(Heroicon::OutlinedLink)
                            ->columnSpan(2),
                        TextInput::make('govwin_link')
                            ->label('GovWin reference URL')
                            ->url()
                            ->prefixIcon(Heroicon::OutlinedLink)
                            ->columnSpanFull(),
                    ]),
                Section::make('Summary')
                    ->columns(1)
                    ->components([
                        Textarea::make('source_description')
                            ->label('Source description')
                            ->autosize()
                            ->rows(3),
                        Textarea::make('source_requirements')
                            ->label('Source requirements')
                            ->autosize()
                            ->rows(3),
                    ]),
            ]);
    }

    private static function decisionTab(): Tab
    {
        return Tab::make('Decision')
            ->icon(Heroicon::OutlinedFlag)
            ->columns(1)
            ->components([
                Section::make('Bid decision')
                    ->description('Choosing Bid or No Bid requires who ordered it and a reason — same rule the public board enforces. Prefer the "Change Decision" button above the table for a one-click version of this.')
                    ->columns(2)
                    ->components([
                        Select::make('decision')
                            ->options(self::DECISIONS)
                            ->default('Pending')
                            ->required()
                            ->live()
                            ->native(false),
                        Select::make('bid_priority')
                            ->label('Bid priority')
                            ->options(self::BID_PRIORITIES)
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('decision') === 'Bid')
                            ->required(fn (Get $get): bool => $get('decision') === 'Bid'),
                        TextInput::make('decision_by')
                            ->label('Decided by')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => self::requiresReason($get('decision')))
                            ->required(fn (Get $get): bool => self::requiresReason($get('decision'))),
                        DatePicker::make('decision_date')
                            ->native(false)
                            ->visible(fn (Get $get): bool => self::requiresReason($get('decision'))),
                        Textarea::make('decision_comment')
                            ->label('Reason')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::requiresReason($get('decision')))
                            ->required(fn (Get $get): bool => self::requiresReason($get('decision'))),
                    ]),
                Section::make('Scoring')
                    ->columns(2)
                    ->components([
                        TextInput::make('probability')
                            ->label('Probability of win')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->required(),
                    ]),
            ]);
    }

    private static function captureTab(): Tab
    {
        return Tab::make('Capture & Ownership')
            ->icon(Heroicon::OutlinedUserGroup)
            ->columns(1)
            ->components([
                Section::make('Ownership')
                    ->columns(3)
                    ->components([
                        TextInput::make('alqimi_sme')
                            ->label('ALQIMI SME'),
                    ]),
                Section::make('Next action')
                    ->columns(2)
                    ->components([
                        Textarea::make('next_action')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                        DatePicker::make('action_due')->native(false),
                    ]),
            ]);
    }

    private static function gapAnalysisTab(): Tab
    {
        return Tab::make('Gap Analysis')
            ->icon(Heroicon::OutlinedShieldExclamation)
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->components([
                        Select::make('gap_status')
                            ->options(array_combine(OpportunityModal::GAP_STATUSES, OpportunityModal::GAP_STATUSES))
                            ->default('Not Assessed')
                            ->native(false),
                        TextInput::make('gap_owner'),
                        Textarea::make('gap')
                            ->label('Gap')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('gap_mitigation')
                            ->label('Mitigation plan')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function competitiveTab(): Tab
    {
        return Tab::make('Competitive & Incumbent')
            ->icon(Heroicon::OutlinedScale)
            ->columns(1)
            ->components([
                Section::make('Competitive landscape')
                    ->columns(2)
                    ->components([
                        Select::make('competitive_position')
                            ->options(array_combine(OpportunityModal::COMPETITIVE_POSITIONS, OpportunityModal::COMPETITIVE_POSITIONS))
                            ->default('Unknown')
                            ->native(false),
                        TextInput::make('competitive_next_action'),
                        Textarea::make('competitors')
                            ->helperText('One per line, or separated by ";" / ",".')
                            ->autosize()
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('competitive_analysis')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('competitive_discriminators')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('teaming')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Incumbent')
                    ->columns(2)
                    ->components([
                        TextInput::make('incumbent')->default('Unknown'),
                        TextInput::make('incumbent_contract'),
                        TextInput::make('incumbent_award_value'),
                        TextInput::make('incumbent_period'),
                        TextInput::make('incumbent_customer_relationship')
                            ->label('Customer relationship')
                            ->columnSpanFull(),
                        TextInput::make('incumbent_source')
                            ->label('Verification source URL')
                            ->url()
                            ->prefixIcon(Heroicon::OutlinedLink)
                            ->columnSpanFull(),
                        Textarea::make('incumbent_brief')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('incumbent_performance')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('incumbent_strengths')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('incumbent_weaknesses')
                            ->autosize()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function rfpTab(): Tab
    {
        return Tab::make('RFP & Evaluation')
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(1)
                    ->components([
                        TextInput::make('rfp_format'),
                        Textarea::make('rfp_instructions')
                            ->autosize()
                            ->rows(3),
                        Textarea::make('rfp_sections')
                            ->autosize()
                            ->rows(3),
                        Textarea::make('evaluation_factors')
                            ->autosize()
                            ->rows(3),
                    ]),
            ]);
    }

    private static function aiInsightsTab(): Tab
    {
        return Tab::make('AI Insights')
            ->icon(Heroicon::OutlinedSparkles)
            ->columns(1)
            ->components([
                Section::make('Generated analysis')
                    ->description('Read-only — populated by the AI analysis job once that phase is enabled. Nothing here is editable by hand.')
                    ->columns(1)
                    ->components([
                        Textarea::make('ai_executive_summary')->autosize()->rows(3)->disabled(),
                        Textarea::make('ai_why_it_matters')->autosize()->rows(3)->disabled(),
                        Textarea::make('ai_red_team_critique')->autosize()->rows(3)->disabled(),
                        Textarea::make('ai_competitive_outlook')->autosize()->rows(3)->disabled(),
                    ]),
            ]);
    }

    private static function requiresReason(?string $decision): bool
    {
        return in_array($decision, self::DECISIONS_REQUIRING_REASON, true);
    }

    /**
     * @return array<string, string>
     */
    private static function setAsideOptions(?Opportunity $record): array
    {
        $options = array_combine(OpportunityModal::SET_ASIDE_OPTIONS, OpportunityModal::SET_ASIDE_OPTIONS);

        if ($record?->set_aside !== null && ! isset($options[$record->set_aside])) {
            $options[$record->set_aside] = $record->set_aside.' (existing)';
        }

        return $options;
    }
}
