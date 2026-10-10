@php
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow p-5';
    $total = $all->count();
    $pct = min(100, round($total / max(1, $plant->max_capacity) * 100));
@endphp

<div class="py-6" wire:poll.3s>
    <div class="max-w-7xl mx-auto px-4 space-y-6">

        {{-- CONTADOR PRINCIPAL --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $plant->name }} · actualizado {{ now()->format('H:i:s') }}</p>
                    <p class="text-7xl font-extrabold text-gray-900 dark:text-gray-100 leading-none">{{ $total }}</p>
                    <p class="text-lg text-gray-600 dark:text-gray-300">personas externas en planta</p>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    @foreach (['visita' => 'Visitas', 'proveedor' => 'Proveedores', 'contratista' => 'Contratistas'] as $key => $label)
                        <div class="rounded-lg bg-gray-100 dark:bg-gray-700 px-4 py-2">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $byType[$key] ?? 0 }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-300">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-4 h-3 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full {{ $pct > 85 ? 'bg-red-500' : ($pct > 60 ? 'bg-amber-500' : 'bg-green-500') }}"
                     style="width: {{ $pct }}%"></div>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Aforo: {{ $total }} / {{ $plant->max_capacity }}</p>
        </div>

        {{-- POR ÁREA --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <button wire:click="$set('areaId', '')"
                    class="{{ $card }} text-left {{ $areaId === '' ? 'ring-2 ring-amber-500' : '' }}">
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $total }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-300">Todas las áreas</p>
            </button>
            @foreach ($areas as $area)
                <button wire:click="$set('areaId', '{{ $area->id }}')"
                        class="{{ $card }} text-left {{ (string) $areaId === (string) $area->id ? 'ring-2 ring-amber-500' : '' }}">
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $all->where('area_id', $area->id)->count() }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $area->name }}</p>
                </button>
            @endforeach
        </div>

        {{-- LISTA --}}
        <div class="{{ $card }} overflow-x-auto">
            <table class="w-full text-left text-gray-900 dark:text-gray-100">
                <thead class="text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="py-2">Nombre</th><th>Empresa</th><th>Tipo</th>
                        <th>Área</th><th>Gafete</th><th>Entrada</th><th>Tiempo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                            <td class="py-3 font-semibold">{{ $log->visit->visitor->name }}</td>
                            <td>{{ $log->visit->visitor->company }}</td>
                            <td>{{ ucfirst($log->visit->type) }}</td>
                            <td>{{ $log->area?->name ?? '—' }}</td>
                            <td>{{ $log->badge?->number ?? '—' }}</td>
                            <td>{{ $log->checked_in_at->format('H:i') }}</td>
                            <td>{{ $log->checked_in_at->diffForHumans(null, true) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-500 dark:text-gray-400">Nadie dentro en esta área.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
