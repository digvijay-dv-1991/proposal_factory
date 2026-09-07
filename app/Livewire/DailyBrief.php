<?php

namespace App\Livewire;

use App\Models\Opportunity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DailyBrief extends Component
{
    /**
     * Fixed taxonomy sections the brief is broken out by, matching the
     * reference's renderDailyBrief() section list (General appears there
     * too; MISC is intentionally excluded, same as the reference).
     *
     * @var array<int, string>
     */
    private const SECTIONS = ['CBRN', 'FOCI', 'DFaaS', 'DoD Intelligence - Ops', 'Health', 'Digitization', 'General'];

    /**
     * @return EloquentCollection<int, Opportunity>
     */
    private function active(): EloquentCollection
    {
        return Opportunity::query()->where('decision', '!=', 'No Bid')->get();
    }

    /**
     * @return array{recent: int, monitoring: int, capture_window: int, needs_action: int, pending: int}
     */
    #[Computed]
    public function stats(): array
    {
        $active = $this->active();

        return [
            'recent' => $active->filter(fn (Opportunity $o) => $o->discovered_at?->greaterThanOrEqualTo(now()->subDay()))->count(),
            'monitoring' => $active->where('decision', 'Monitoring')->count(),
            'capture_window' => $this->withinDays($active, 14, 31)->count(),
            'needs_action' => $this->needsAction($active)->count(),
            'pending' => $active->where('decision', 'Pending')->count(),
        ];
    }

    /**
     * Opportunities discovered in the last 24 hours.
     *
     * @return EloquentCollection<int, Opportunity>
     */
    #[Computed]
    public function recentlyAdded(): EloquentCollection
    {
        return $this->active()
            ->filter(fn (Opportunity $o) => $o->discovered_at?->greaterThanOrEqualTo(now()->subDay()))
            ->values();
    }

    /**
     * Opportunities due within 31 days, most urgent first.
     *
     * @return EloquentCollection<int, Opportunity>
     */
    #[Computed]
    public function captureWindow(): EloquentCollection
    {
        return $this->withinDays($this->active(), 0, 31)->sortBy('response_due')->values();
    }

    /**
     * Bid opportunities missing a next action or a capture owner.
     *
     * @return EloquentCollection<int, Opportunity>
     */
    #[Computed]
    public function needsManagementAttention(): EloquentCollection
    {
        return $this->needsAction($this->active())->values();
    }

    /**
     * Each taxonomy section's opportunities that are either recently added
     * or due soon, for the per-section breakdown.
     *
     * @return array<string, EloquentCollection<int, Opportunity>>
     */
    #[Computed]
    public function sectionUpdates(): array
    {
        $active = $this->active();

        return collect(self::SECTIONS)->mapWithKeys(function (string $section) use ($active) {
            $inSection = $active->where('origin', $section);
            $relevant = $inSection->filter(fn (Opportunity $o) => $o->discovered_at?->greaterThanOrEqualTo(now()->subDay())
                || ($o->response_due !== null && $o->response_due->greaterThanOrEqualTo(now()) && $o->response_due->lessThanOrEqualTo(now()->addDays(31))));

            return [$section => $relevant->values()];
        })->all();
    }

    /**
     * @param  EloquentCollection<int, Opportunity>  $opportunities
     * @return EloquentCollection<int, Opportunity>
     */
    private function withinDays(EloquentCollection $opportunities, int $min, int $max): EloquentCollection
    {
        return $opportunities->filter(function (Opportunity $o) use ($min, $max) {
            if ($o->response_due === null) {
                return false;
            }

            $days = now()->startOfDay()->diffInDays($o->response_due->startOfDay(), false);

            return $days >= $min && $days <= $max;
        });
    }

    /**
     * @param  EloquentCollection<int, Opportunity>  $opportunities
     * @return EloquentCollection<int, Opportunity>
     */
    private function needsAction(EloquentCollection $opportunities): EloquentCollection
    {
        return $opportunities->filter(fn (Opportunity $o) => $o->decision === 'Bid'
            && (trim((string) $o->next_action) === '' || trim((string) $o->capture_owner) === '' || $o->capture_owner === 'Unassigned'));
    }

    public function render(): View
    {
        return view('livewire.daily-brief');
    }
}
