@if ($resumen)
    @php
        $producto = $resumen['producto'];
        $num = fn (float $n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
        $stock = (float) $producto->stock_actual;
        $minimo = (float) $producto->stock_minimo;
        $nivel = $stock <= 0 ? 'sin' : ($stock <= $minimo ? 'bajo' : 'ok');
    @endphp

    <section class="dm-panel dm-kardex-resumen">
        <div class="dm-kardex-producto">
            <div class="dm-ingreso-etiqueta">Producto</div>
            <div class="dm-tarjeta-titulo dm-tarjeta-titulo--grande">{{ $producto->nombre }}</div>
            <div class="dm-celda-sub"><span class="dm-mono">{{ $producto->sku }}</span>{{ $producto->categoria ? ' · '.$producto->categoria : '' }}</div>
        </div>
        <div class="dm-kardex-cifras">
            <div>
                <div class="dm-ingreso-etiqueta">Stock actual</div>
                <div class="dm-mono dm-kardex-cifra dm-stock-valor--{{ $nivel }}">{{ $num($stock) }} <span class="dm-celda-sub">/ mín. {{ $num($minimo) }}</span></div>
            </div>
            <div>
                <div class="dm-ingreso-etiqueta">Entradas · 90 días</div>
                <div class="dm-mono dm-kardex-cifra dm-texto-lima">+{{ $num($resumen['entradas']) }}</div>
            </div>
            <div>
                <div class="dm-ingreso-etiqueta">Salidas · 90 días</div>
                <div class="dm-mono dm-kardex-cifra dm-texto-rojo">−{{ $num($resumen['salidas']) }}</div>
            </div>
        </div>
    </section>
@endif
