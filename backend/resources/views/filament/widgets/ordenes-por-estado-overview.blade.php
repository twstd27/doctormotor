<x-filament-widgets::widget>
    <x-filament::section heading="Órdenes por estado">
        <div class="divide-y divide-gray-800">
            @foreach ($filas as $fila)
                <div class="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                    <div class="flex items-center gap-2.5">
                        <span class="size-2 shrink-0 rounded-full {{ $fila['dotClass'] }}"></span>
                        <span class="text-sm text-gray-300">{{ $fila['label'] }}</span>
                    </div>
                    <span class="text-sm font-semibold text-white">{{ $fila['total'] }}</span>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
