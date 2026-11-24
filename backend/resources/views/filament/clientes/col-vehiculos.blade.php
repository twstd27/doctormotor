@php($vehiculos = $getRecord()->vehiculos)
@if ($vehiculos->isEmpty())
    <span class="dm-celda-sub">Sin vehículos</span>
@else
    <div class="dm-placas">
        @foreach ($vehiculos->take(3) as $vehiculo)
            <x-dm.placa>{{ $vehiculo->placa }}</x-dm.placa>
        @endforeach
        @if ($vehiculos->count() > 3)
            <span class="dm-celda-sub">+{{ $vehiculos->count() - 3 }}</span>
        @endif
    </div>
@endif
