@php
    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
@endphp

<div class="dm-stats dm-stats--3">
    <div class="dm-stat {{ $porPagar > 0 ? 'dm-stat--saldo' : '' }}">
        <div class="dm-stat-etiqueta">Total por pagar</div>
        <div class="dm-stat-valor dm-mono {{ $porPagar > 0 ? 'dm-texto-ambar' : '' }}">{{ $bs($porPagar) }}</div>
        <div class="dm-celda-sub">{{ $cuentas }} {{ $cuentas === 1 ? 'cuenta' : 'cuentas' }} · {{ $proveedores }} {{ $proveedores === 1 ? 'proveedor' : 'proveedores' }}</div>
    </div>
    <div class="dm-stat">
        <div class="dm-stat-etiqueta">Ya pagado</div>
        <div class="dm-stat-valor dm-mono dm-texto-lima">{{ $bs($pagado) }}</div>
        <div class="dm-celda-sub">Abonos y pagos registrados</div>
    </div>
    <div class="dm-stat {{ $vencidas > 0 ? 'dm-stat--falla' : '' }}">
        <div class="dm-stat-etiqueta">Vencidas</div>
        <div class="dm-stat-valor dm-mono {{ $vencidas > 0 ? 'dm-texto-rojo' : '' }}">{{ $vencidas }}</div>
        <div class="dm-celda-sub">{{ $vencidas > 0 ? 'Pasaron su fecha de vencimiento' : 'Ninguna pasó su fecha de vencimiento' }}</div>
    </div>
</div>
