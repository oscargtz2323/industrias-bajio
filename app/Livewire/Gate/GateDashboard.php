<?php

namespace App\Livewire\Gate;

use App\Models\AccessLog;
use App\Models\Area;
use App\Models\Badge;
use App\Models\Plant;
use App\Models\User;
use App\Models\Visit;
use App\Services\AccessService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class GateDashboard extends Component
{
    use WithFileUploads;

    public int $plantId;

    public $name = '', $company = '', $id_document = '', $phone = '', $reason = '';
    public $type = 'visita', $vehicle_plate = '', $vehicle_model = '';
    public $host_id = '', $area_id = '', $badge_id = '';
    public $photo;
    public $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('guardia', 'seguridad', 'admin'), 403);
        $this->plantId = auth()->user()->plant_id ?? Plant::value('id');
    }

    public function checkIn(AccessService $access): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'id_document' => 'required|string|max:60',
            'company' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'type' => 'required|in:visita,proveedor,contratista',
            'host_id' => 'nullable|exists:users,id',
            'area_id' => 'nullable|exists:areas,id',
            'badge_id' => 'nullable|exists:badges,id',
            'reason' => 'nullable|string|max:255',
            'vehicle_plate' => 'nullable|string|max:15',
            'vehicle_model' => 'nullable|string|max:60',
            'photo' => 'nullable|image|max:4096',
        ]);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        $log = $access->checkInWalkIn($this->plantId, $data, auth()->user());

        $this->reset(['name', 'company', 'id_document', 'phone', 'reason', 'type',
            'vehicle_plate', 'vehicle_model', 'host_id', 'area_id', 'badge_id', 'photo']);
        session()->flash('ok', "Entrada registrada. Gafete {$log->badge->number}");
    }

    public function checkInExpected(int $visitId, AccessService $access): void
    {
        $visit = Visit::where('plant_id', $this->plantId)->findOrFail($visitId);
        $log = $access->checkInVisit($visit, [], auth()->user());
        session()->flash('ok', "Entrada registrada. Gafete {$log->badge->number}");
    }

    public function checkOut(int $logId, AccessService $access): void
    {
        $log = AccessLog::where('plant_id', $this->plantId)->findOrFail($logId);
        $access->checkOut($log, auth()->user());
        session()->flash('ok', 'Salida registrada.');
    }

    public function render(AccessService $access)
    {
        $inside = $access->inside($this->plantId)
            ->when($this->search, fn ($q) => $q->whereHas('visit.visitor', fn ($v) => $v
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('company', 'like', "%{$this->search}%")))
            ->latest('checked_in_at')->get();

        return view('livewire.gate.gate-dashboard', [
            'inside' => $inside,
            'expected' => Visit::with(['visitor', 'host'])
                ->where('plant_id', $this->plantId)
                ->whereIn('status', ['preregistrada', 'aprobada'])
                ->whereDate('scheduled_for', today())
                ->orderBy('scheduled_for')->get(),
            'hosts' => User::where('role', 'anfitrion')->orderBy('name')->get(),
            'areas' => Area::where('plant_id', $this->plantId)->orderBy('name')->get(),
            'badges' => Badge::where('plant_id', $this->plantId)
                ->where('status', 'disponible')->orderBy('number')->get(),
        ]);
    }
}
