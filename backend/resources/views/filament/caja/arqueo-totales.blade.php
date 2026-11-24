@php
    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
    $diferencia = round($contado - $esperado, 2);
    $estado = abs($diferencia) < 0.005 ? 'ok' : ($diferencia > 0 ? 'sobra' : 'falta');
    $textoDif = $estado === 'ok'
        ? 'Bs 0,00'
        : ($diferencia > 0 ? '+ ' : '− ').$bs(abs($diferencia));
@endphp

<div class="dm-arqueo">
    <div class="dm-arqueo-totales">
        <div class="dm-stat">
            <div class="dm-stat-etiqueta">Debe haber</div>
            <div class="dm-stat-valor dm-mono">{{ $bs($esperado) }}</div>
        </div>
        <div class="dm-stat">
            <div class="dm-stat-etiqueta">Contado</div>
            <div class="dm-stat-valor dm-mono">{{ $bs($contado) }}</div>
        </div>
        <div class="dm-stat dm-arqueo-dif dm-arqueo-dif--{{ $estado }}">
            <div class="dm-stat-etiqueta">Diferencia</div>
            <div class="dm-stat-valor dm-mono">{{ $textoDif }}</div>
        </div>
    </div>

    @if ($estado === 'ok' && $contado > 0)
        <div class="dm-aviso dm-aviso--ok">La caja cuadra. Puedes cerrarla.</div>
    @endif

    @if ($qr > 0 || $tarjeta > 0)
        <div class="dm-aviso">
            QR ({{ $bs($qr) }}) y tarjeta ({{ $bs($tarjeta) }}) no se cuentan aquí: se concilian con el banco.
        </div>
    @endif
</div>
