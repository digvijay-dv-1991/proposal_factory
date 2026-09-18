<?php

namespace App\Livewire;

use App\Models\IndustryEvent;
use App\Models\Opportunity;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OpportunityCalendar extends Component
{
    /**
     * 'solicitation' plots opportunity response due dates only — the
     * reference explicitly excludes release/action/milestone dates here.
     * 'events' plots the curated industry-events calendar.
     */
    public string $mode = 'solicitation';

    public int $year;

    public int $month;

    public function mount(string $mode): void
    {
        $this->mode = $mode;
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function previousMonth(): void
    {
        $this->shiftMonth(-1);
    }

    public function nextMonth(): void
    {
        $this->shiftMonth(1);
    }

    private function shiftMonth(int $delta): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonthsNoOverflow($delta);
        $this->year = $date->year;
        $this->month = $date->month;
    }

    #[Computed]
    public function title(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    /**
     * Every event in the visible month, keyed by day-of-month. Solicitation
     * mode plots each opportunity on its response due date only. Events
     * mode plots each industry event on every day within its start/end
     * range that falls in this month (matching the reference's
     * `iso>=ev.start&&iso<=ev.end` day-by-day membership check).
     */
    #[Computed]
    public function eventsByDay(): Collection
    {
        $start = Carbon::create($this->year, $this->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        if ($this->mode === 'events') {
            return IndustryEvent::query()
                ->where('starts_on', '<=', $end)
                ->where('ends_on', '>=', $start)
                ->get()
                ->flatMap(function (IndustryEvent $event) use ($start, $end): array {
                    $entries = [];
                    $cursor = $event->starts_on->max($start)->copy();
                    $last = $event->ends_on->min($end);

                    while ($cursor->lte($last)) {
                        $entries[] = [
                            'day' => (int) $cursor->day,
                            'title' => $event->name,
                            'subtitle' => $event->location,
                            'className' => $event->class_name,
                        ];
                        $cursor->addDay();
                    }

                    return $entries;
                })
                ->groupBy('day');
        }

        return Opportunity::query()
            ->whereBetween('response_due', [$start, $end])
            ->get()
            ->map(fn (Opportunity $o): array => [
                'day' => (int) $o->response_due->day,
                'title' => $o->name,
                'subtitle' => 'Response due · '.$o->agency,
            ])
            ->groupBy('day');
    }

    /**
     * The full calendar grid: leading/trailing days from adjacent months
     * fill out complete weeks, matching the reference's calendar-grid.
     *
     * @return Collection<int, array{date: Carbon, inMonth: bool, isToday: bool}>
     */
    #[Computed]
    public function days(): Collection
    {
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);
        $gridStart = $firstOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = collect();
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $days->push([
                'date' => $cursor->copy(),
                'inMonth' => $cursor->month === $this->month,
                'isToday' => $cursor->isToday(),
            ]);
            $cursor->addDay();
        }

        return $days;
    }

    public function render(): View
    {
        return view('livewire.opportunity-calendar');
    }
}
