@php($orden = $getRecord())
<div class="dm-celda">
    <span class="dm-mono dm-celda-codigo">{{ $orden->codigo }}</span>
    <span class="dm-celda-sub">{{ $orden->fecha_ingreso?->diffForHumans() }}</span>
</div>
