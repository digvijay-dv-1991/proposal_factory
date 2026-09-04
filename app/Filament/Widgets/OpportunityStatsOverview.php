<?php

namespace App\Filament\Widgets;

use App\Models\Opportunity;
use App\Support\CompactNumber;
use Closure;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OpportunityStatsOverview extends StatsOverviewWidget
{
    /**
     * Own row above the two charts below it — see OpportunityDecisionChart's
     * docblock for the rest of the dashboard's layout reasoning.
     */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    private const TREND_DAYS = 14;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $totalCount = Opportunity::query()->count();
        $openValue = (float) Opportunity::query()->where('decision', '!=', 'No Bid')->sum('value');
        $pendingCount = Opportunity::query()->where('decision', 'Pending')->count();
        $bidCount = Opportunity::query()->where('decision', 'Bid')->count();
        $noBidCount = Opportunity::query()->where('decision', 'No Bid')->count();
        $winRate = $bidCount + $noBidCount > 0
            ? (int) round(($bidCount / ($bidCount + $noBidCount)) * 100)
            : null;

        return [
            Stat::make('Total opportunities', (string) $totalCount)
                ->description('Everything tracked, every phase')
                ->descriptionIcon(Heroicon::OutlinedRectangleStack)
                ->color('gray'),
            Stat::make('Open pipeline', CompactNumber::money($openValue))
                ->description('Total value across everything not marked No Bid')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->chart(self::dailyValueAddedTrend())
                ->color('primary'),
            Stat::make('Awaiting decision', (string) $pendingCount)
                ->description('Opportunities still marked Pending')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->chart(self::dailyAddedCountTrend())
                ->color('warning'),
            Stat::make('Bid', (string) $bidCount)
                ->description('Committed to pursue')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('No Bid', (string) $noBidCount)
                ->description('Declined')
                ->descriptionIcon(Heroicon::OutlinedXCircle)
                ->color('danger'),
            Stat::make('Win rate', $winRate !== null ? $winRate.'%' : '—')
                ->description($winRate !== null ? 'Bid vs. No Bid, of decided opportunities' : 'No decided opportunities yet')
                ->descriptionIcon(Heroicon::OutlinedTrophy)
                ->color('primary'),
        ];
    }

    /**
     * Real trend, not decorative filler: total value of opportunities added
     * per day over the last two weeks (date_added is the only time-series
     * column this table actually has — there's no historical value
     * snapshot to chart "open pipeline" itself over time).
     *
     * @return array<int, float>
     */
    private static function dailyValueAddedTrend(): array
    {
        $totals = Opportunity::query()
            ->where('date_added', '>=', now()->subDays(self::TREND_DAYS - 1)->toDateString())
            ->selectRaw('date_added, SUM(value) as total')
            ->groupBy('date_added')
            ->pluck('total', 'date_added');

        return self::fillTrend($totals, fn (mixed $total): float => (float) $total);
    }

    /**
     * @return array<int, int>
     */
    private static function dailyAddedCountTrend(): array
    {
        $counts = Opportunity::query()
            ->where('date_added', '>=', now()->subDays(self::TREND_DAYS - 1)->toDateString())
            ->selectRaw('date_added, COUNT(*) as total')
            ->groupBy('date_added')
            ->pluck('total', 'date_added');

        return self::fillTrend($counts, fn (mixed $total): int => (int) $total);
    }

    /**
     * @param  Collection<string, mixed>  $byDate
     * @return array<int, mixed>
     */
    private static function fillTrend(Collection $byDate, Closure $cast): array
    {
        return collect(range(self::TREND_DAYS - 1, 0))
            ->map(function (int $daysAgo) use ($byDate, $cast) {
                $date = Carbon::today()->subDays($daysAgo)->toDateString();

                return $cast($byDate[$date] ?? 0);
            })
            ->all();
    }
}
