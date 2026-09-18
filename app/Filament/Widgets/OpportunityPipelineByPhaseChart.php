<?php

namespace App\Filament\Widgets;

use App\Livewire\OpportunityBoard;
use App\Models\Opportunity;
use Filament\Widgets\ChartWidget;

/**
 * Pairs with OpportunityDecisionChart below OpportunityStatsOverview — see
 * that class's docblock for the dashboard's overall layout.
 */
class OpportunityPipelineByPhaseChart extends ChartWidget
{
    protected ?string $heading = 'Pipeline by phase';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $phases = OpportunityBoard::PHASES;

        $counts = Opportunity::query()
            ->selectRaw('phase, count(*) as aggregate')
            ->groupBy('phase')
            ->pluck('aggregate', 'phase');

        return [
            'datasets' => [
                [
                    'label' => 'Opportunities',
                    'data' => array_map(fn (string $phase): int => (int) ($counts[$phase] ?? 0), $phases),
                    'backgroundColor' => '#e6d11c',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $phases,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            // Sizes the canvas to the card's actual height rather than its
            // own preferred aspect ratio (see OpportunityDecisionChart's
            // getOptions() for the other half of why this card and its
            // sibling used to end up different heights).
            'maintainAspectRatio' => false,
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
