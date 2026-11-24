@php
    $cuenta = $getRecord();
    $original = max((float) $cuenta->monto_original, 0.01);
    $pagado = max($original - (float) $cuenta->saldo_pendiente, 0);
    $porcentaje = round(min($pagado / $original, 1) * 100);
@endphp
<div class="dm-pagado">
    <div class="dm-medidor"><span class="dm-stock-barra" style="width: {{ $porcentaje }}%"></span></div>
    <div class="dm-celda-sub dm-mono">{{ number_format($pagado, 2, ',', '.') }} de {{ number_format($original, 2, ',', '.') }}</div>
</div>
