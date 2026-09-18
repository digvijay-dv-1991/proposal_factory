<?php

namespace App\Livewire;

use App\Models\MarketPartner;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PartnerDirectory extends Component
{
    /**
     * @return Collection<int, MarketPartner>
     */
    #[Computed]
    public function partners(): Collection
    {
        return MarketPartner::query()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.partner-directory');
    }
}
