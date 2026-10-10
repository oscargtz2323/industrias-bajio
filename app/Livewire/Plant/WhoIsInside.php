<?php

namespace App\Livewire\Plant;

use App\Models\Area;
use App\Models\Plant;
use App\Services\AccessService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class WhoIsInside extends Component
{
    public int $plantId;
    public $areaId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('guardia', 'seguridad', 'admin'), 403);
        $this->plantId = auth()->user()->plant_id ?? Plant::value('id');
    }

    public function render(AccessService $access)
    {
        $all = $access->inside($this->plantId)->latest('checked_in_at')->get();
        $logs = $this->areaId ? $all->where('area_id', (int) $this->areaId) : $all;

        return view('livewire.plant.who-is-inside', [
            'plant' => Plant::find($this->plantId),
            'areas' => Area::where('plant_id', $this->plantId)->orderBy('name')->get(),
            'all' => $all,
            'logs' => $logs,
            'byType' => $all->groupBy(fn ($l) => $l->visit->type)->map->count(),
        ]);
    }
}
