<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-4">
            <label class="text-xs font-medium text-gray-400">Desde</label>
            <input
                type="date"
                wire:model.live="desde"
                class="mt-1 w-full rounded-lg border-gray-700 bg-gray-800 text-sm text-white focus:border-primary-500 focus:ring-primary-500"
            />
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-4">
            <label class="text-xs font-medium text-gray-400">Hasta</label>
            <input
                type="date"
                wire:model.live="hasta"
                class="mt-1 w-full rounded-lg border-gray-700 bg-gray-800 text-sm text-white focus:border-primary-500 focus:ring-primary-500"
            />
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-4">
            <p class="text-xs font-medium text-gray-400">Ingresos</p>
            <p class="mt-1 text-2xl font-bold text-lime-400">Bs {{ number_format($this->totalIngresos(), 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-800 bg-gray-900 p-4">
            <p class="text-xs font-medium text-gray-400">Egresos</p>
            <p class="mt-1 text-2xl font-bold text-amber-400">Bs {{ number_format($this->totalEgresos(), 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-xl border p-4 {{ $this->resultado() >= 0 ? 'border-lime-800 bg-lime-950/30' : 'border-red-800 bg-red-950/30' }}">
        <p class="text-xs font-medium text-gray-400">{{ $this->resultado() >= 0 ? 'Ganancia' : 'Pérdida' }} del período</p>
        <p class="mt-1 text-3xl font-bold {{ $this->resultado() >= 0 ? 'text-lime-400' : 'text-red-400' }}">
            Bs {{ number_format(abs($this->resultado()), 2) }}
        </p>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-800 bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-800 text-xs text-gray-400">
                    <th class="px-4 py-3 font-medium">Fecha</th>
                    <th class="px-4 py-3 font-medium">Ingresos</th>
                    <th class="px-4 py-3 font-medium">Egresos</th>
                    <th class="px-4 py-3 font-medium">Resultado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->desglose() as $fila)
                    <tr class="border-b border-gray-800/60 last:border-0">
                        <td class="px-4 py-2.5 text-white">{{ \Illuminate\Support\Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 text-lime-400">Bs {{ number_format($fila['ingresos'], 2) }}</td>
                        <td class="px-4 py-2.5 text-amber-400">Bs {{ number_format($fila['egresos'], 2) }}</td>
                        <td class="px-4 py-2.5 {{ $fila['resultado'] >= 0 ? 'text-lime-400' : 'text-red-400' }}">
                            Bs {{ number_format($fila['resultado'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sin movimientos en este rango de fechas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
