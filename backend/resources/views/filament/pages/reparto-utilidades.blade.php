<x-filament-panels::page>
    @php
        $suma = $this->sumaPorcentajes();
        $sumaOk = $suma === 100.0;
        $repartos = $this->repartos();
    @endphp

    {{-- Reglas de reparto vigentes --}}
    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <h2 class="text-base font-bold text-white">Reglas de reparto vigentes</h2>
        <p class="mt-1 text-sm text-gray-400">Porcentaje de la utilidad neta que le corresponde a cada socio. Deben sumar 100%.</p>

        @if (empty($reglas))
            <p class="mt-4 text-sm text-gray-500">No hay socios activos todavía — crea uno en "Socios" primero.</p>
        @else
            <div class="mt-4 divide-y divide-gray-800">
                @foreach ($reglas as $i => $regla)
                    <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <span class="text-sm font-medium text-white">{{ $regla['nombre'] }}</span>
                        <div style="width: 130px;">
                            <x-filament::input.wrapper suffix="%">
                                <x-filament::input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    wire:model.live="reglas.{{ $i }}.porcentaje"
                                />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-gray-800 pt-4">
                <span class="text-sm font-semibold {{ $sumaOk ? 'text-lime-400' : 'text-red-400' }}">
                    Suma: {{ number_format($suma, 2) }}%
                    @unless ($sumaOk)
                        — debe sumar 100%
                    @endunless
                </span>
                <x-filament::button wire:click="guardarReglas" :disabled="! $sumaOk" color="primary">
                    Guardar reglas
                </x-filament::button>
            </div>
        @endif
    </div>

    {{-- Generar reparto --}}
    <div class="mt-4 rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <h2 class="text-base font-bold text-white">Generar reparto</h2>
        <p class="mt-1 text-sm text-gray-400">Calcula la utilidad neta del período (ingresos − costos directos − gastos) y la reparte según las reglas vigentes.</p>

        <div class="mt-4 flex flex-wrap items-end gap-3">
            <div style="flex: 1 1 150px; min-width: 150px;">
                <label class="text-xs font-medium text-gray-400">Desde</label>
                <x-filament::input.wrapper class="mt-1">
                    <x-filament::input type="date" wire:model="periodoInicio" />
                </x-filament::input.wrapper>
                @error('periodoInicio') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div style="flex: 1 1 150px; min-width: 150px;">
                <label class="text-xs font-medium text-gray-400">Hasta</label>
                <x-filament::input.wrapper class="mt-1">
                    <x-filament::input type="date" wire:model="periodoFin" />
                </x-filament::input.wrapper>
                @error('periodoFin') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <x-filament::button wire:click="generarReparto" color="primary">
                Generar reparto
            </x-filament::button>
        </div>
    </div>

    {{-- Historial de repartos --}}
    <div class="mt-4 rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <h2 class="text-base font-bold text-white">Historial de repartos</h2>

        @if ($repartos->isEmpty())
            <p class="mt-4 text-sm text-gray-500">Todavía no se generó ningún reparto.</p>
        @else
            <div class="mt-4 divide-y divide-gray-800">
                @foreach ($repartos as $reparto)
                    <div x-data="{ open: false }" class="py-3 first:pt-0 last:pb-0">
                        <button type="button" x-on:click="open = ! open" class="flex w-full items-center justify-between gap-4 text-left">
                            <div>
                                <p class="text-sm font-medium text-white">
                                    {{ \Illuminate\Support\Carbon::parse($reparto->periodo_inicio)->format('d/m/Y') }}
                                    –
                                    {{ \Illuminate\Support\Carbon::parse($reparto->periodo_fin)->format('d/m/Y') }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Generado por {{ $reparto->generadoPor?->nombre ?? '—' }} el {{ $reparto->generado_at?->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm font-bold tabular-nums {{ $reparto->utilidad_neta >= 0 ? 'text-lime-400' : 'text-red-400' }}">
                                    Bs {{ number_format($reparto->utilidad_neta, 2) }}
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-gray-500 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-bind:style="open && 'transform: rotate(180deg)'">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </button>

                        <div x-show="open" x-cloak class="mt-3 rounded-xl bg-gray-950/40 p-4">
                            <div class="grid grid-cols-3 gap-3 text-xs text-gray-400">
                                <div>
                                    <p class="uppercase tracking-wide">Ingresos</p>
                                    <p class="mt-1 text-sm font-semibold text-white tabular-nums">Bs {{ number_format($reparto->ingresos_total, 2) }}</p>
                                </div>
                                <div>
                                    <p class="uppercase tracking-wide">Costos directos</p>
                                    <p class="mt-1 text-sm font-semibold text-white tabular-nums">Bs {{ number_format($reparto->costos_directos_total, 2) }}</p>
                                </div>
                                <div>
                                    <p class="uppercase tracking-wide">Gastos</p>
                                    <p class="mt-1 text-sm font-semibold text-white tabular-nums">Bs {{ number_format($reparto->gastos_total, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 divide-y divide-gray-800 border-t border-gray-800 pt-3">
                                @foreach ($reparto->detalle as $detalle)
                                    <div class="flex items-center justify-between py-2 text-sm first:pt-0 last:pb-0">
                                        <span class="text-gray-300">{{ $detalle->socio?->nombre ?? '—' }} <span class="text-gray-500">({{ number_format($detalle->porcentaje_aplicado, 2) }}%)</span></span>
                                        <span class="font-semibold tabular-nums text-lime-300">Bs {{ number_format($detalle->monto, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
