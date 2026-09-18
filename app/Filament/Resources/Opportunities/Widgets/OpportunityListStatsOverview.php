<?php

namespace App\Filament\Resources\Opportunities\Widgets;

use App\Models\Opportunity;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Deliberately different from OpportunityStatsOverview on the dashboard —
 * that one is portfolio-level (totals, mix, trend) for a glance at the
 * whole pipeline; this one is operational, answering "what needs my
 * attention in this list right now" for someone actively working it.
 */
class OpportunityListStatsOverview extends StatsOverviewWidget
{
    /**
     * @return array<string, int>
     */
    protected function getColumns(): array
    {
        return ['default' => 1, 'sm' => 2, 'lg' => 4];
    }

    protected function getStats(): array
    {
        $overdue = Opportunity::query()
            ->whereNotIn('decision', ['Bid', 'No Bid'])
            ->whereDate('response_due', '<', now())
            ->count();

        $dueThisWeek = Opportunity::query()
            ->whereNotIn('decision', ['Bid', 'No Bid'])
            ->whereBetween('response_due', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        $needsOwner = Opportunity::query()
            ->where('decision', '!=', 'No Bid')
            ->where(fn (Builder $query) => $query->whereNull('capture_owner')->orWhere('capture_owner', ''))
            ->count();

        $avgProbability = (int) round(
            Opportunity::query()->where('decision', '!=', 'No Bid')->avg('probability') ?? 0
        );

        return [
            Stat::make('Overdue response', (string) $overdue)
                ->description($overdue > 0 ? 'Past due and still undecided' : 'Nothing overdue')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($overdue > 0 ? 'danger' : 'success'),
            Stat::make('Due this week', (string) $dueThisWeek)
                ->description('Response window closes within 7 days')
                ->descriptionIcon(Heroicon::OutlinedBellAlert)
                ->color($dueThisWeek > 0 ? 'warning' : 'gray'),
            Stat::make('Needs an owner', (string) $needsOwner)
                ->description('Active, no capture owner assigned')
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->color($needsOwner > 0 ? 'info' : 'gray'),
            Stat::make('Avg. win probability', $avgProbability.'%')
                ->description('Across everything still active')
                ->descriptionIcon(Heroicon::OutlinedTrophy)
                ->color('primary'),
        ];
    }
}
