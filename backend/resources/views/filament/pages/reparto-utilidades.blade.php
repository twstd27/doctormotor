<x-filament-panels::page>
    @php
        $suma = $this->sumaPorcentajes();
        $sumaOk = $suma === 100.0;
        $repartos = $this->repartos();
        $utilidadPreview = $this->utilidadPreview();
        $colores = ['lime', 'cyan', 'amber', 'gray'];
        $rangos = [['hoy', 'Hoy'], ['7dias', '7 días'], ['estemes', 'Este mes'], ['ano', 'Año']];
    @endphp

    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
        {{-- Reglas de reparto vigentes --}}
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <h2 class="text-base font-bold text-white">Reglas de reparto vigentes</h2>
            <p class="mt-1 text-sm text-gray-400">Deben sumar exactamente 100%.</p>

            @if (empty($reglas))
                <div class="mt-4 rounded-xl border border-dashed border-gray-700 px-4 py-8 text-center">
                    <p class="text-sm text-gray-500">No hay socios activos todavía.</p>
                    <a href="{{ \App\Filament\Resources\Socios\SocioResource::getUrl('create') }}" class="mt-2 inline-block text-sm font-medium text-lime-400 hover:underline">
                        Añadir socio
                    </a>
                </div>
            @else
                <div class="mt-4 divide-y divide-gray-800">
                    @foreach ($reglas as $i => $regla)
                        @php $color = $colores[$i % count($colores)]; @endphp
                        <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-full bg-gray-800 text-sm font-semibold text-{{ $color }}-300">
                                    {{ static::iniciales($regla['nombre']) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-[15px] font-medium text-white">{{ $regla['nombre'] }}</p>
                                    <p class="text-xs text-gray-500">Le corresponde Bs {{ number_format($utilidadPreview * ($regla['porcentaje'] / 100), 2) }}</p>
                                </div>
                            </div>
                            <div style="width: 110px;">
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

                <div class="mt-4 flex h-[7px] overflow-hidden rounded-full bg-gray-800">
                    @if ($sumaOk)
                        @foreach ($reglas as $i => $regla)
                            @php $color = $colores[$i % count($colores)]; @endphp
                            <div class="bg-{{ $color }}-400" style="width: {{ $regla['porcentaje'] }}%;"></div>
                        @endforeach
                    @else
                        <div class="w-full bg-amber-400"></div>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    @if ($sumaOk)
                        <span class="flex items-center gap-1.5 text-sm font-medium text-lime-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Suma 100% — listo para guardar
                        </span>
                    @else
                        @php $diferencia = round(100 - $suma, 2); @endphp
                        <span class="flex items-center gap-1.5 text-sm font-medium text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            Suma {{ number_format($suma, 2) }}% — {{ $diferencia > 0 ? 'falta '.number_format($diferencia, 2).'%' : 'sobra '.number_format(abs($diferencia), 2).'%' }}
                        </span>
                    @endif

                    <x-filament::button wire:click="guardarReglas" :disabled="! $sumaOk" color="primary">
                        Guardar reglas
                    </x-filament::button>
                </div>
            @endif
        </div>

        {{-- Generar reparto --}}
        <div id="generar-reparto" class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <h2 class="text-base font-bold text-white">Generar reparto</h2>
            <p class="mt-1 text-sm text-gray-400">Ingresos − costos directos − gastos, repartido según las reglas vigentes.</p>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-400">Desde</label>
                    <x-filament::input.wrapper class="mt-1">
                        <x-filament::input type="date" wire:model.live="periodoInicio" />
                    </x-filament::input.wrapper>
                    @error('periodoInicio') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-400">Hasta</label>
                    <x-filament::input.wrapper class="mt-1">
                        <x-filament::input type="date" wire:model.live="periodoFin" />
                    </x-filament::input.wrapper>
                    @error('periodoFin') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($rangos as [$tipo, $label])
                    <button
                        type="button"
                        wire:click="aplicarRango('{{ $tipo }}')"
                        @class([
                            'flex h-9 items-center rounded-full border px-3.5 text-xs font-medium transition-colors',
                            'border-lime-800 bg-gray-800 text-lime-300' => $this->rangoActivo() === $tipo,
                            'border-gray-700 bg-transparent text-gray-400 hover:bg-gray-800' => $this->rangoActivo() !== $tipo,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl bg-gray-950/40 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Vista previa del período</p>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Ingresos</span>
                        <span class="font-medium tabular-nums text-lime-400">Bs {{ number_format($this->totalIngresos(), 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Costos directos</span>
                        <span class="font-medium tabular-nums text-white">Bs {{ number_format($this->totalCostosDirectos(), 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Gastos</span>
                        <span class="font-medium tabular-nums text-white">Bs {{ number_format($this->totalGastos(), 2) }}</span>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-gray-800 pt-3">
                    <span class="text-sm font-semibold text-white">Utilidad neta a repartir</span>
                    <span class="text-xl font-bold tabular-nums {{ $utilidadPreview >= 0 ? 'text-lime-300' : 'text-red-400' }}">Bs {{ number_format($utilidadPreview, 2) }}</span>
                </div>
            </div>

            <x-filament::button wire:click="generarReparto" color="primary" class="mt-4 w-full justify-center">
                Generar reparto
            </x-filament::button>
        </div>
    </div>

    {{-- Historial de repartos --}}
    <div class="mt-4 rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white">Historial de repartos</h2>
            <span class="text-xs text-gray-500">{{ $repartos->count() }} {{ $repartos->count() === 1 ? 'reparto generado' : 'repartos generados' }}</span>
        </div>

        @if ($repartos->isEmpty())
            <p class="mt-4 text-sm text-gray-500">
                Todavía no se generó ningún reparto — <a href="#generar-reparto" class="text-lime-400 hover:underline">genera el primero arriba</a>.
            </p>
        @else
            <div class="mt-4 divide-y divide-gray-800">
                @foreach ($repartos as $reparto)
                    <div x-data="{ open: false }" class="py-3 first:pt-0 last:pb-0">
                        <button type="button" x-on:click="open = ! open" class="flex w-full items-center justify-between gap-4 text-left">
                            <div class="min-w-0">
                                <p class="text-[14.5px] font-semibold text-white">
                                    {{ \Illuminate\Support\Carbon::parse($reparto->periodo_inicio)->format('d/m/Y') }}
                                    – {{ \Illuminate\Support\Carbon::parse($reparto->periodo_fin)->format('d/m/Y') }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Generado por {{ $reparto->generadoPor?->nombre ?? '—' }} el {{ $reparto->generado_at?->format('d/m/Y · H:i') }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="rounded-full bg-gray-800 px-2.5 py-1 text-xs text-gray-400">
                                    {{ $reparto->detalle->count() }} {{ $reparto->detalle->count() === 1 ? 'socio' : 'socios' }}
                                </span>
                                <span class="text-sm font-bold tabular-nums text-lime-300">Bs {{ number_format($reparto->utilidad_neta, 2) }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-gray-500 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-bind:style="open && 'transform: rotate(180deg)'">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </button>

                        <div x-show="open" x-cloak class="mt-3 rounded-xl bg-gray-950/40 p-4">
                            <div class="divide-y divide-gray-800">
                                @foreach ($reparto->detalle as $detalle)
                                    <div class="flex items-center justify-between py-2 text-sm first:pt-0 last:pb-0">
                                        <span class="text-gray-300">{{ $detalle->socio?->nombre ?? '—' }}</span>
                                        <span class="flex items-center gap-3">
                                            <span class="text-xs text-gray-500">{{ number_format($detalle->porcentaje_aplicado, 2) }}%</span>
                                            <span class="w-24 text-right font-semibold tabular-nums text-white">Bs {{ number_format($detalle->monto, 2) }}</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <a
                                    href="{{ route('admin-pdf.reparto-utilidades.comprobante', $reparto) }}"
                                    target="_blank"
                                    class="flex items-center gap-1.5 rounded-lg border border-gray-700 px-3 py-2 text-xs font-medium text-gray-300 transition-colors hover:bg-gray-800"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    Descargar comprobante
                                </a>
                                <button
                                    type="button"
                                    x-on:click="if (confirm('¿Anular este reparto? Esta acción no se puede deshacer.')) $wire.anularReparto({{ $reparto->id }})"
                                    class="flex items-center gap-1.5 rounded-lg border-[#3a2733] px-3 py-2 text-xs font-medium text-red-400 transition-colors hover:bg-[#1e1622]"
                                    style="border-width: 1px;"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Anular reparto
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
