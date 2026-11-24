@php
    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
    $num = fn (float $n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');

    $total = collect($items)->sum(fn ($i) => (float) ($i['cantidad'] ?? 0) * (float) ($i['precio_unitario'] ?? 0));
    $unidades = collect($items)->sum(fn ($i) => (float) ($i['cantidad'] ?? 0));
    $productos = count($items);

    // Cuántos productos dejan de estar en o bajo su mínimo con esta compra.
    $ids = collect($items)->pluck('producto_id')->filter()->all();
    $modelos = \App\Models\Producto::whereIn('id', $ids)->get()->keyBy('id');
    $salenDeBajo = collect($items)->filter(function ($i) use ($modelos) {
        $p = $modelos->get($i['producto_id'] ?? null);

        return $p
            && (float) $p->stock_actual <= (float) $p->stock_minimo
            && (float) $p->stock_actual + (float) ($i['cantidad'] ?? 0) > (float) $p->stock_minimo;
    })->count();
@endphp

<section class="dm-panel">
    <dl class="dm-cuenta-lineas">
        <div><dt>Ítems</dt><dd class="dm-mono">{{ $productos }} {{ $productos === 1 ? 'producto' : 'productos' }} · {{ $num($unidades) }} u.</dd></div>
    </dl>

    <div class="dm-compra-total">
        <div class="dm-ingreso-etiqueta">Total de la compra</div>
        <div class="dm-cuenta-saldo dm-mono">{{ $bs($total) }}</div>
    </div>

    <div class="dm-compra-efectos">
        <div class="dm-compra-efectos-titulo">Al guardar:</div>
        @if ($productos > 0)
            <div><span class="dm-texto-lima">↓</span> Sube el stock de {{ $productos }} {{ $productos === 1 ? 'producto' : 'productos' }}{{ $salenDeBajo > 0 ? ' ('.$salenDeBajo.' '.($salenDeBajo === 1 ? 'sale' : 'salen').' de bajo mínimo)' : '' }}</div>
        @else
            <div class="dm-celda-sub">Agrega productos para ver qué cambia.</div>
        @endif
        @if ($total > 0)
            <div><span class="dm-texto-ambar">●</span> Cuenta por pagar de {{ $bs($total) }}{{ $proveedor ? ' a '.$proveedor : '' }}</div>
        @endif
    </div>
</section>
