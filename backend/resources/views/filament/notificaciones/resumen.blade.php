<div class="dm-pila">
@if ($fallidos > 0)
    <div class="dm-aviso dm-aviso--rojo dm-aviso--fila">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
        <span class="dm-aviso-texto">
            <strong>{{ $fallidos }} {{ $fallidos === 1 ? 'mensaje no se pudo enviar' : 'mensajes no se pudieron enviar' }}.</strong>
            {{ $ultimoError }}
        </span>
        @if ($urlReconectar)
            <a class="dm-aviso-accion dm-aviso-accion--rojo" href="{{ $urlReconectar }}">Reconectar WhatsApp</a>
        @endif
    </div>
@endif

<div class="dm-stats dm-stats--3">
    <div class="dm-stat">
        <div class="dm-stat-etiqueta">Enviados · 30 días</div>
        <div class="dm-stat-valor dm-mono">{{ $enviados30 }}</div>
        <div class="dm-celda-sub">de {{ $total30 }} intentos</div>
    </div>
    <div class="dm-stat">
        <div class="dm-stat-etiqueta">Tasa de envío</div>
        <div class="dm-stat-valor dm-mono dm-texto-lima">{{ $total30 > 0 ? round($enviados30 / $total30 * 100) : 0 }}%</div>
        <div class="dm-celda-sub">Meta aceptó el mensaje</div>
    </div>
    <div class="dm-stat {{ $fallidos > 0 ? 'dm-stat--falla' : '' }}">
        <div class="dm-stat-etiqueta">Fallidos</div>
        <div class="dm-stat-valor dm-mono {{ $fallidos > 0 ? 'dm-texto-rojo' : '' }}">{{ $fallidos }}</div>
        <div class="dm-celda-sub">{{ $fallidos > 0 ? 'Pendientes de reintento' : 'Ninguno pendiente' }}</div>
    </div>
</div>
</div>
