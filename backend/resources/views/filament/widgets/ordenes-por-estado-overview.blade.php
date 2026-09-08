<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Órdenes por estado
        </x-slot>
        <x-slot name="afterHeader">
            <span class="text-xs font-medium text-gray-500">{{ $totalActivas }} activas</span>
        </x-slot>

        <div class="divide-y divide-gray-800">
            @foreach ($filas as $fila)
                <a
                    href="{{ route('filament.admin.resources.ordenes-trabajo.index', ['tableFilters' => ['estado' => ['value' => $fila['estado']]]]) }}"
                    class="flex items-center justify-between py-2 first:pt-0 last:pb-0 hover:bg-gray-800/40 -mx-2 px-2 rounded"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="size-2 shrink-0 rounded-full {{ $fila['dotClass'] }}"></span>
                        <span class="text-sm text-gray-300">{{ $fila['label'] }}</span>
                    </div>
                    <span class="text-sm font-semibold {{ $fila['total'] === 0 ? 'text-gray-500' : 'text-white' }}">{{ $fila['total'] }}</span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
