@php
    $orden = $getRecord();
    $tecnico = $orden->tecnicoAsignado;
    $iniciales = $tecnico
        ? collect(preg_split('/\s+/', trim($tecnico->nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : null;
@endphp
@if ($tecnico)
    <div class="dm-tecnico">
        <span class="dm-avatar dm-avatar--sm dm-avatar--azul" aria-hidden="true">{{ $iniciales }}</span>
        <span class="dm-tecnico-nombre">{{ $tecnico->nombre }}</span>
    </div>
@else
    <span class="dm-celda-sub">Sin asignar</span>
@endif
