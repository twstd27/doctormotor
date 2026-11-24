@php
    $usuario = $getRecord();
    $iniciales = collect(preg_split('/\s+/', trim($usuario->nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<div class="dm-cliente-celda">
    <span class="dm-avatar dm-avatar--md {{ $usuario->rol === 'operador_tecnico' ? 'dm-avatar--azul' : '' }}" aria-hidden="true">{{ $iniciales }}</span>
    <div class="dm-celda">
        <span class="dm-celda-titulo">{{ $usuario->nombre }}</span>
        <span class="dm-celda-sub">{{ $usuario->email ?: ($usuario->telefono_whatsapp ?: 'Sin contacto') }}</span>
    </div>
</div>
