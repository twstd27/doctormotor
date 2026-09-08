@php
    $items = [
        ['label' => 'Cliente', 'value' => $vehiculo->cliente?->nombre, 'href' => $vehiculo->cliente ? route('filament.admin.resources.clientes.edit', $vehiculo->cliente) : null],
        ['label' => 'Placa', 'value' => $vehiculo->placa],
        ['label' => 'Marca', 'value' => $vehiculo->marca],
        ['label' => 'Modelo', 'value' => $vehiculo->modelo],
        ['label' => 'Año', 'value' => $vehiculo->anio],
        ['label' => 'Color', 'value' => $vehiculo->color],
        ['label' => 'Motor', 'value' => $vehiculo->motor ?: '—'],
        ['label' => 'Kilometraje', 'value' => number_format($vehiculo->kilometraje_actual, 0, ',', '.').' km'],
    ];
@endphp

<div class="grid gap-x-6 gap-y-4" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));">
    @foreach ($items as $item)
        <div class="border-b border-gray-800/60 pb-3">
            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">{{ $item['label'] }}</p>
            @if (! empty($item['href']))
                <a href="{{ $item['href'] }}" class="mt-1 block truncate text-sm font-medium text-lime-300 hover:underline">{{ $item['value'] }}</a>
            @else
                <p class="mt-1 truncate text-sm font-medium text-white">{{ $item['value'] }}</p>
            @endif
        </div>
    @endforeach
</div>
