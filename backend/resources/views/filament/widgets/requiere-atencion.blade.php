<x-filament-widgets::widget>
    <x-filament::section heading="Requiere atención">
        <div class="divide-y divide-gray-800">
            @foreach ($filas as $fila)
                <a href="{{ $fila['url'] }}" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0 hover:bg-gray-800/40 -mx-2 px-2 rounded">
                    <span class="w-10 shrink-0 text-xl font-bold tabular-nums {{ $fila['claseTexto'] }}">{{ $fila['total'] }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-white">{{ $fila['label'] }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $fila['descripcion'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
