<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Opportunities\Schemas\OpportunityForm;
use App\Models\Opportunity;
use Filament\Widgets\ChartWidget;

/**
 * Dashboard layout: OpportunityStatsOverview takes the full-width row above
 * (sort 1), then this chart and OpportunityPipelineByPhaseChart share the
 * row below it as two equal half-width cards (sort 2 & 3, both columnSpan
 * 1 on the panel's 2-column grid). What actually keeps the two cards the
 * same height end to end is the `.fi-wi-chart-frame` / canvas height rule
 * in resources/css/filament/admin-theme.css — $maxHeight here just has to
 * agree with it. The legend `position` below and `maintainAspectRatio`
 * matter too, but only as good practice on top of that CSS rule, not as a
 * substitute for it (see the CSS comment for why $maxHeight/aspect-ratio
 * options alone don't reliably produce a matched-height row).
 */
class OpportunityDecisionChart extends ChartWidget
{
    protected ?string $heading = 'Decision mix';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '260px';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $decisions = array_keys(OpportunityForm::DECISIONS);

        $counts = Opportunity::query()
            ->selectRaw('decision, count(*) as aggregate')
            ->groupBy('decision')
            ->pluck('aggregate', 'decision');

        return [
            'datasets' => [
                [
                    'data' => array_map(fn (string $decision): int => (int) ($counts[$decision] ?? 0), $decisions),
                    'backgroundColor' => [
                        '#9ea0a3', // Pending — gray
                        '#60a5fa', // More Info — info
                        '#f59e0b', // Monitoring — warning
                        '#e6d11c', // Shape — brand gold
                        '#22c55e', // Bid — success
                        '#ef4444', // No Bid — danger
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $decisions,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            // Every chart widget on this dashboard sets this so Chart.js
            // sizes the canvas to the card's actual height instead of its
            // own preferred aspect ratio.
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    // 'bottom' put the legend in its own row under the
                    // donut, making this card taller than its sibling
                    // OpportunityPipelineByPhaseChart (no legend) — the grid
                    // then stretches both cards to match this one's height,
                    // leaving dead space under the bar chart that has no
                    // legend row of its own to fill. 'right' keeps the
                    // legend beside the donut instead of adding a row below
                    // it, so both cards end up the same height naturally.
                    'position' => 'right',
                ],
            ],
        ];
    }
}
