<?php

namespace App\Livewire;

use App\Models\MarketCompetitor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CompetitorDirectory extends Component
{
    /**
     * @return Collection<int, MarketCompetitor>
     */
    #[Computed]
    public function competitors(): Collection
    {
        return MarketCompetitor::query()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.competitor-directory');
    }
}
