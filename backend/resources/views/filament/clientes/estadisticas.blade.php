@php
    use App\Filament\Resources\Pagos\PagoResource;

    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
    $visitas = $cliente->ordenesTrabajo()->count();
    $primera = $cliente->ordenesTrabajo()->min('fecha_ingreso');
    $facturado = $cliente->totalFacturado();
    $saldo = $cliente->saldoPendiente();
    $ticket = $visitas > 0 ? $facturado / $visitas : 0;
@endphp

<div class="dm-stats dm-stats--3">
    <div class="dm-stat">
        <div class="dm-stat-etiqueta">Visitas</div>
        <div class="dm-stat-valor dm-mono">{{ $visitas }}</div>
        <div class="dm-celda-sub">
            {{ $primera ? 'Cliente desde '.\Illuminate\Support\Carbon::parse($primera)->translatedFormat('M Y') : 'Todavía sin visitas' }}
        </div>
    </div>
    <div class="dm-stat">
        <div class="dm-stat-etiqueta">Total facturado</div>
        <div class="dm-stat-valor dm-mono">{{ $bs($facturado) }}</div>
        <div class="dm-celda-sub">{{ $visitas > 0 ? 'Ticket promedio '.$bs($ticket) : 'Sin presupuestos aprobados' }}</div>
    </div>
    <div class="dm-stat {{ $saldo > 0.009 ? 'dm-stat--saldo' : '' }}">
        <div class="dm-stat-etiqueta">{{ $saldo < -0.009 ? 'Saldo a favor' : 'Saldo pendiente' }}</div>
        <div class="dm-stat-valor dm-mono {{ $saldo > 0.009 ? 'dm-texto-ambar' : ($saldo < -0.009 ? 'dm-texto-lima' : '') }}">{{ $bs(abs($saldo)) }}</div>
        @if ($saldo > 0.009 && PagoResource::canCreate())
            <a class="dm-stat-enlace" href="{{ PagoResource::getUrl('create', ['cliente_id' => $cliente->id]) }}">Registrar cobro →</a>
        @endif
    </div>
</div>
