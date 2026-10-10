<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Badge;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccessService
{
    /** Entrada de alguien que llega sin pre-registro (walk-in). */
    public function checkInWalkIn(int $plantId, array $data, User $guard): AccessLog
    {
        return DB::transaction(function () use ($plantId, $data, $guard) {
            $visitor = Visitor::firstOrCreate(
                ['id_document' => $data['id_document']],
                [
                    'name' => $data['name'],
                    'company' => $data['company'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]
            );

            $visit = Visit::create([
                'plant_id' => $plantId,
                'visitor_id' => $visitor->id,
                'host_id' => $data['host_id'] ?? null,
                'area_id' => $data['area_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'type' => $data['type'] ?? 'visita',
                'scheduled_for' => now(),
                'status' => 'aprobada',
                'qr_token' => Str::random(40),
            ]);

            return $this->checkInVisit($visit, $data, $guard);
        });
    }

    /** Entrada de una visita (pre-registrada o recién creada). */
    public function checkInVisit(Visit $visit, array $data, User $guard): AccessLog
    {
        return DB::transaction(function () use ($visit, $data, $guard) {
            $visit->loadMissing('visitor');

            // 1. Lista de restricción
            if ($visit->visitor->is_blocked) {
                throw ValidationException::withMessages([
                    'id_document' => 'ACCESO DENEGADO: ' . $visit->visitor->name
                        . ' está bloqueado (' . ($visit->visitor->block_reason ?? 'sin motivo') . ').',
                ]);
            }

            // 2. No puede entrar dos veces sin haber salido
            $yaDentro = AccessLog::whereNull('checked_out_at')
                ->whereHas('visit', fn ($q) => $q->where('visitor_id', $visit->visitor_id))
                ->exists();
            if ($yaDentro) {
                throw ValidationException::withMessages([
                    'id_document' => $visit->visitor->name . ' ya está dentro de la planta.',
                ]);
            }

            // 3. Gafete: el elegido o el siguiente disponible
            $badge = ! empty($data['badge_id'])
                ? Badge::lockForUpdate()->find($data['badge_id'])
                : Badge::where('plant_id', $visit->plant_id)
                    ->where('status', 'disponible')->orderBy('id')->lockForUpdate()->first();

            if (! $badge || $badge->status !== 'disponible') {
                throw ValidationException::withMessages(['badge_id' => 'No hay gafetes disponibles.']);
            }
            $badge->update(['status' => 'asignado']);

            // 4. Foto (opcional)
            $photo = Arr::get($data, 'photo');
            $photoPath = $photo ? $photo->store('entradas', 'public') : null;

            // 5. Registro de acceso
            $log = AccessLog::create([
                'visit_id' => $visit->id,
                'plant_id' => $visit->plant_id,
                'area_id' => $visit->area_id,
                'badge_id' => $badge->id,
                'guard_in_id' => $guard->id,
                'checked_in_at' => now(),
                'entry_photo_path' => $photoPath,
                'vehicle_plate' => Arr::get($data, 'vehicle_plate'),
                'vehicle_model' => Arr::get($data, 'vehicle_model'),
            ]);

            $visit->update(['status' => 'en_planta']);

            return $log;
        });
    }

    /** Salida en 1 clic: cierra el registro y libera el gafete. */
    public function checkOut(AccessLog $log, User $guard): AccessLog
    {
        return DB::transaction(function () use ($log, $guard) {
            $log = AccessLog::lockForUpdate()->findOrFail($log->id);
            if ($log->checked_out_at) {
                return $log; // ya había salido
            }

            $log->update(['checked_out_at' => now(), 'guard_out_id' => $guard->id]);
            $log->visit->update(['status' => 'finalizada']);
            $log->badge?->update(['status' => 'disponible']);

            return $log;
        });
    }

    /** "Quién está dentro" = registros sin hora de salida. */
    public function inside(int $plantId)
    {
        return AccessLog::with(['visit.visitor', 'area', 'badge'])
            ->where('plant_id', $plantId)
            ->whereNull('checked_out_at');
    }
}