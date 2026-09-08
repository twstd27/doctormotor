<x-filament-widgets::widget>
    <x-filament::section heading="Actividad de la semana">
        <div class="flex items-baseline justify-between">
            <span class="text-2xl font-bold tabular-nums text-white">{{ $totalSemana }}</span>
            <span class="text-xs font-medium {{ $diferencia < 0 ? 'text-red-400' : 'text-gray-500' }}">
                {{ $diferencia === 0 ? 'Igual que la semana pasada' : ($diferencia > 0 ? "+{$diferencia} vs. semana pasada" : "{$diferencia} vs. semana pasada") }}
            </span>
        </div>

        <div class="mt-4 flex items-end justify-between gap-2" style="height: 64px;">
            @foreach ($barras as $barra)
                <div class="flex h-full flex-1 flex-col justify-end">
                    <div
                        class="w-full rounded-t {{ $barra['esHoy'] ? 'bg-lime-400' : 'bg-lime-400/40' }}"
                        style="height: {{ $barra['alturaPx'] }}px;"
                        title="{{ $barra['total'] }}"
                    ></div>
                </div>
            @endforeach
        </div>
        <div class="mt-1.5 flex justify-between">
            @foreach ($barras as $barra)
                <span class="flex-1 text-center text-[11px] font-medium {{ $barra['esHoy'] ? 'text-lime-300' : 'text-gray-500' }}">{{ $barra['label'] }}</span>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
