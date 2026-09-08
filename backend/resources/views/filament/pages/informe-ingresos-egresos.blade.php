<x-filament-panels::page>
    @php
        $filas = $this->desglose();
        $ingresos = $this->totalIngresos();
        $egresos = $this->totalEgresos();
        $resultado = $this->resultado();
        $margen = $this->margenPorcentaje();
        $rangos = [
            ['hoy', 'Hoy'],
            ['7dias', '7 días'],
            ['estemes', 'Este mes'],
            ['ano', 'Año'],
        ];
    @endphp

    {{-- Barra de filtros --}}
    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div style="flex: 1 1 150px; min-width: 150px;">
                <label class="text-xs font-medium text-gray-400">Desde</label>
                <x-filament::input.wrapper class="mt-1">
                    <x-filament::input type="date" wire:model.live="desde" />
                </x-filament::input.wrapper>
            </div>
            <div style="flex: 1 1 150px; min-width: 150px;">
                <label class="text-xs font-medium text-gray-400">Hasta</label>
                <x-filament::input.wrapper class="mt-1">
                    <x-filament::input type="date" wire:model.live="hasta" />
                </x-filament::input.wrapper>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($rangos as [$tipo, $label])
                    <button
                        type="button"
                        wire:click="aplicarRango('{{ $tipo }}')"
                        @class([
                            'flex h-9 min-w-[44px] items-center rounded-full border px-3.5 text-xs font-medium transition-colors',
                            'border-lime-800 bg-gray-800 text-lime-300' => $this->rangoActivo() === $tipo,
                            'border-gray-700 bg-transparent text-gray-400 hover:bg-gray-800' => $this->rangoActivo() !== $tipo,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-4">
            <p class="text-xs font-medium text-gray-400">Ingresos</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-lime-400">Bs {{ number_format($ingresos, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-4">
            <p class="text-xs font-medium text-gray-400">Egresos</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-amber-400">Bs {{ number_format($egresos, 2) }}</p>
        </div>
        <div class="rounded-xl border p-4 {{ $resultado >= 0 ? 'border-lime-800 bg-lime-950/30' : 'border-red-800 bg-red-950/30' }}">
            <p class="text-xs font-medium text-gray-400">{{ $resultado >= 0 ? 'Ganancia' : 'Pérdida' }} del período</p>
            <p
                class="mt-1 font-bold tabular-nums {{ $resultado >= 0 ? 'text-lime-300' : 'text-red-400' }}"
                style="font-size: clamp(26px, 3.8vw, 34px);"
            >
                Bs {{ number_format(abs($resultado), 2) }}
            </p>
            @if ($margen !== null)
                <p class="mt-1 text-xs text-gray-400">margen de {{ number_format($margen, 1) }}%</p>
            @endif
        </div>
    </div>

    {{-- Detalle por día --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-800 px-4 py-3">
            <h2 class="text-sm font-semibold text-white">Detalle por día</h2>
            <span class="text-xs text-gray-400">{{ count($filas) }} {{ count($filas) === 1 ? 'registro' : 'registros' }}</span>
        </div>

        @if (count($filas) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-950/40 text-[11px] font-medium tracking-wide text-gray-500 uppercase">
                            <th class="px-4 py-2.5">Fecha</th>
                            <th class="px-4 py-2.5 text-right">Ingresos</th>
                            <th class="px-4 py-2.5 text-right">Egresos</th>
                            <th class="px-4 py-2.5 text-right">Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $fila)
                            <tr class="border-b border-gray-800/60 last:border-0 hover:bg-gray-800/40">
                                <td class="px-4 py-2.5 text-white">{{ \Illuminate\Support\Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-lime-400">Bs {{ number_format($fila['ingresos'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-400">Bs {{ number_format($fila['egresos'], 2) }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold tabular-nums text-white">Bs {{ number_format($fila['resultado'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-950/40">
                            <td class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase">Total</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-lime-400">Bs {{ number_format($ingresos, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-400">Bs {{ number_format($egresos, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $resultado >= 0 ? 'text-lime-300' : 'text-red-400' }}">
                                Bs {{ number_format($resultado, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="px-4 py-10 text-center">
                <p class="text-sm text-gray-500">Sin movimientos en este rango de fechas.</p>
                <button type="button" wire:click="aplicarRango('estemes')" class="mt-2 text-sm font-medium text-lime-400 hover:underline">
                    Ver este mes
                </button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
