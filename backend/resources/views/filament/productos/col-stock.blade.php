@php
    $producto = $getRecord();
    $stock = (float) $producto->stock_actual;
    $minimo = (float) $producto->stock_minimo;
    // La barra llena al doble del mínimo; en rojo sin stock, en ámbar en o bajo el mínimo.
    $tope = max($minimo * 2, $stock, 1);
    $ancho = round(min($stock / $tope, 1) * 100);
    $nivel = $stock <= 0 ? 'sin' : ($stock <= $minimo ? 'bajo' : 'ok');
    $num = fn (float $n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp
<div class="dm-stock">
    <span class="dm-mono dm-stock-valor dm-stock-valor--{{ $nivel }}">{{ $num($stock) }}</span>
    <div class="dm-stock-barra-ctn">
        <div class="dm-medidor"><span class="dm-stock-barra dm-stock-barra--{{ $nivel }}" style="width: {{ $ancho }}%"></span></div>
        <div class="dm-celda-sub">mín. {{ $num($minimo) }}{{ $producto->unidad_medida ? ' · '.$producto->unidad_medida : '' }}</div>
    </div>
</div>
