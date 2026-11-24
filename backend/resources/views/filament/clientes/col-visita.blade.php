@php($ultima = $getRecord()->ultima_visita ? \Illuminate\Support\Carbon::parse($getRecord()->ultima_visita) : null)
@if ($ultima)
    <div class="dm-celda">
        <span class="dm-celda-titulo dm-celda-titulo--normal">{{ $ultima->format('d/m/Y') }}</span>
        <span class="dm-celda-sub">{{ $ultima->diffForHumans() }}</span>
    </div>
@else
    <span class="dm-celda-sub">Sin visitas</span>
@endif
