@php
    $cliente = $getRecord();
    $iniciales = collect(preg_split('/\s+/', trim($cliente->nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<div class="dm-cliente-celda">
    <span class="dm-avatar dm-avatar--md {{ $cliente->user_id ? 'dm-tarjeta-avatar' : '' }}" aria-hidden="true">{{ $iniciales }}</span>
    <div class="dm-celda">
        <span class="dm-celda-titulo">{{ $cliente->nombre }}</span>
        <span class="dm-mono dm-celda-sub">{{ $cliente->ci_nit ? 'CI/NIT '.$cliente->ci_nit : 'Sin CI/NIT' }}</span>
    </div>
</div>
