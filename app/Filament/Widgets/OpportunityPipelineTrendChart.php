<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Wide trend row beneath OpportunityDecisionChart/OpportunityPipelineByPhaseChart
 * — those two answer "what does the pipeline look like right now"; this one
 * answers "is new work actually coming in", the one genuinely time-series
 * question date_added can honestly answer (see OpportunityStatsOverview's
 * docblock on why a fabricated value-over-time chart isn't used instead).
 */
class OpportunityPipelineTrendChart extends ChartWidget
{
    protected ?string $heading = 'New opportunities, last 30 days';

    protected int|string|array $columnSpan = 'full';

    /**
     * Matches admin-theme.css's `.fi-wi-chart-frame` rule, which now forces
     * every dashboard chart to this same explicit height — see that CSS
     * comment (and OpportunityDecisionChart's docblock) for why $maxHeight
     * alone wasn't enough to keep paired charts the same height.
     */
    protected ?string $maxHeight = '260px';

    protected static ?int $sort = 4;

    private const DAYS = 30;

    protected function getData(): array
    {
        $counts = Opportunity::query()
            ->where('date_added', '>=', now()->subDays(self::DAYS - 1)->toDateString())
            ->selectRaw('date_added, COUNT(*) as total')
            ->groupBy('date_added')
            ->pluck('total', 'date_added');

        $labels = collect(range(self::DAYS - 1, 0))
            ->map(fn (int $daysAgo): string => Carbon::today()->subDays($daysAgo)->format('M j'))
            ->all();

        $data = collect(range(self::DAYS - 1, 0))
            ->map(fn (int $daysAgo): int => (int) ($counts[Carbon::today()->subDays($daysAgo)->toDateString()] ?? 0))
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Added',
                    'data' => $data,
                    'borderColor' => '#e6d11c',
                    'backgroundColor' => 'rgba(230, 209, 28, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            // See OpportunityDecisionChart::getOptions()'s docblock.
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
