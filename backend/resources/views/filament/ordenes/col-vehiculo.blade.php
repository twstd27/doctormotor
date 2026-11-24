@php($orden = $getRecord())
<div class="dm-celda">
    <span class="dm-celda-titulo">{{ $orden->vehiculo?->marca }} {{ $orden->vehiculo?->modelo }} {{ $orden->vehiculo?->anio }}</span>
    <span><x-dm.placa>{{ $orden->vehiculo?->placa }}</x-dm.placa></span>
</div>
