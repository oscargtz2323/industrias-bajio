@php
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-lg p-3';
    $label = 'block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1';
    $card  = 'bg-white dark:bg-gray-800 rounded-xl shadow p-5';
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 space-y-6">

        @if (session('ok'))
            <div class="rounded-xl bg-green-600 text-white p-4 text-lg font-semibold">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl bg-red-600 text-white p-4 text-lg font-semibold space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ENTRADA --}}
            <form wire:submit="checkIn" class="{{ $card }} space-y-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Registrar entrada</h2>

                <div><label class="{{ $label }}">Nombre completo *</label>
                    <input type="text" wire:model="name" class="{{ $input }}" autocomplete="off"></div>
                <div><label class="{{ $label }}">Identificación (INE / licencia) *</label>
                    <input type="text" wire:model="id_document" class="{{ $input }}" autocomplete="off"></div>
                <div><label class="{{ $label }}">Empresa</label>
                    <input type="text" wire:model="company" class="{{ $input }}"></div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $label }}">Tipo</label>
                        <select wire:model="type" class="{{ $input }}">
                            <option value="visita">Visita</option>
                            <option value="proveedor">Proveedor</option>
                            <option value="contratista">Contratista</option>
                        </select></div>
                    <div><label class="{{ $label }}">Gafete</label>
                        <select wire:model="badge_id" class="{{ $input }}">
                            <option value="">Automático</option>
                            @foreach ($badges as $b)
                                <option value="{{ $b->id }}">{{ $b->number }}</option>
                            @endforeach
                        </select></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $label }}">Anfitrión</label>
                        <select wire:model="host_id" class="{{ $input }}">
                            <option value="">—</option>
                            @foreach ($hosts as $h)
                                <option value="{{ $h->id }}">{{ $h->name }}</option>
                            @endforeach
                        </select></div>
                    <div><label class="{{ $label }}">Área</label>
                        <select wire:model="area_id" class="{{ $input }}">
                            <option value="">—</option>
                            @foreach ($areas as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select></div>
                </div>

                <div><label class="{{ $label }}">Motivo de la visita</label>
                    <input type="text" wire:model="reason" class="{{ $input }}"></div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="{{ $label }}">Placas</label>
                        <input type="text" wire:model="vehicle_plate" class="{{ $input }} uppercase"></div>
                    <div><label class="{{ $label }}">Vehículo</label>
                        <input type="text" wire:model="vehicle_model" class="{{ $input }}"></div>
                </div>

                <div>
                    <label class="{{ $label }}">Foto</label>
                    <input type="file" wire:model="photo" accept="image/*" capture="environment"
                           class="block w-full text-sm text-gray-600 dark:text-gray-300">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="mt-2 h-24 rounded-lg">
                    @endif
                </div>

                <button type="submit" wire:loading.attr="disabled"
                        class="w-full rounded-xl bg-green-600 hover:bg-green-700 text-white text-xl font-bold py-4">
                    REGISTRAR ENTRADA
                </button>
            </form>

            <div class="lg:col-span-2 space-y-6">

                {{-- ESPERADOS --}}
                <div class="{{ $card }}">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                        Visitas esperadas hoy ({{ $expected->count() }})
                    </h2>
                    @forelse ($expected as $visit)
                        <div class="flex items-center justify-between gap-3 py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $visit->visitor->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $visit->visitor->company }} · {{ ucfirst($visit->type) }} ·
                                    {{ $visit->scheduled_for->format('H:i') }} ·
                                    Anfitrión: {{ $visit->host?->name ?? '—' }}
                                </p>
                            </div>
                            <button wire:click="checkInExpected({{ $visit->id }})"
                                    class="shrink-0 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 text-lg">
                                Entrada
                            </button>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">No hay visitas pendientes.</p>
                    @endforelse
                </div>

                {{-- DENTRO DE PLANTA --}}
                <div class="{{ $card }}" wire:poll.5s>
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Dentro de planta ({{ $inside->count() }})
                        </h2>
                        <input type="search" wire:model.live.debounce.300ms="search"
                               placeholder="Buscar nombre o empresa" class="{{ $input }} sm:w-64">
                    </div>

                    @forelse ($inside as $log)
                        <div class="flex items-center justify-between gap-3 py-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $log->visit->visitor->name }}
                                    <span class="ml-2 rounded bg-amber-500 text-black text-xs font-bold px-2 py-0.5">
                                        Gafete {{ $log->badge?->number ?? '—' }}
                                    </span>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->visit->visitor->company }} · {{ $log->area?->name ?? 'Sin área' }} ·
                                    entró {{ $log->checked_in_at->diffForHumans() }}
                                    @if ($log->vehicle_plate) · 🚗 {{ $log->vehicle_plate }} @endif
                                </p>
                            </div>
                            <button wire:click="checkOut({{ $log->id }})"
                                    wire:confirm="¿Registrar salida de {{ $log->visit->visitor->name }}?"
                                    class="shrink-0 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-3 text-lg">
                                Salida
                            </button>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">Nadie dentro de la planta.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
