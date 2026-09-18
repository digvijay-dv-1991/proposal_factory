<?php

namespace App\Livewire;

use App\Models\ContractVehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ContractVehicleDirectory extends Component
{
    /**
     * @return Collection<int, ContractVehicle>
     */
    #[Computed]
    public function vehicles(): Collection
    {
        return ContractVehicle::query()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.contract-vehicle-directory');
    }
}
