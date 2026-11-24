@php
    $orden = $getRecord();
    $fecha = $orden->fecha_entrega_estimada;
    $cerrada = $orden->estaCerrada();
@endphp
@if (! $fecha)
    <span class="dm-celda-sub">Sin fecha</span>
@elseif ($cerrada)
    <span class="dm-celda-sub">{{ $fecha->format('d/m') }}</span>
@elseif ($fecha->isToday())
    <span class="dm-entrega dm-entrega--hoy">Hoy</span>
@elseif ($fecha->isPast())
    <span class="dm-entrega dm-entrega--vencida">Vencida · {{ $fecha->format('d/m') }}</span>
@else
    <span class="dm-entrega">{{ ucfirst($fecha->translatedFormat('D d/m')) }}</span>
@endif
