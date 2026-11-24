@php
    $num = fn (float $n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp

<div class="dm-stock-solo-lectura">
    <div class="dm-ingreso-etiqueta">Stock actual</div>
    @if ($producto)
        <div class="dm-stock-solo-lectura-valor">
            <span class="dm-mono">{{ $num((float) $producto->stock_actual) }}</span>
            <span class="dm-celda-sub">{{ $producto->unidad_medida }}</span>
        </div>
        <div class="dm-celda-sub">Cambia con compras, salidas a órdenes y ajustes.</div>
    @else
        <div class="dm-stock-solo-lectura-valor"><span class="dm-mono">0</span></div>
        <div class="dm-celda-sub">Un producto nuevo empieza en 0; el stock entra al registrar una compra.</div>
    @endif
</div>
