@php
    use App\Filament\Resources\OrdenesTrabajo\RelationManagers\PagosRelationManager;
    use App\Filament\Resources\Pagos\PagoResource;

    $aprobado = $orden->totalAprobado();
    $costos = (float) $orden->costosDirectos()->sum('costo_total');
    $margen = $aprobado - $costos;
    $porcentaje = $aprobado > 0 ? round($margen / $aprobado * 100) : null;
    $saldo = $orden->saldoPendiente();
    // El margen es dato de dirección: el cajero no lo ve.
    $verMargen = auth()->user()?->rol === 'super_admin';
    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
@endphp

<div class="dm-stats {{ $verMargen ? 'dm-stats--4' : 'dm-stats--3' }}">
    <div class="dm-stat">
        <div class="dm-stat-etiqueta">Presupuesto aprobado</div>
        <div class="dm-stat-valor dm-mono">{{ $bs($aprobado) }}</div>
    </div>
    <div class="dm-stat">
        <div class="dm-stat-etiqueta">Costos directos</div>
        <div class="dm-stat-valor dm-mono">{{ $bs($costos) }}</div>
    </div>
    @if ($verMargen)
        <div class="dm-stat">
            <div class="dm-stat-etiqueta">Margen estimado</div>
            <div class="dm-stat-valor dm-mono {{ $margen >= 0 ? 'dm-texto-lima' : 'dm-texto-rojo' }}">
                {{ $bs($margen) }}
                @if ($porcentaje !== null)
                    <span class="dm-stat-porcentaje">{{ $porcentaje }}%</span>
                @endif
            </div>
        </div>
    @endif
    <div class="dm-stat {{ $saldo > 0.009 ? 'dm-stat--saldo' : '' }}">
        <div class="dm-stat-etiqueta">{{ $saldo < -0.009 ? 'Saldo a favor del cliente' : 'Saldo por cobrar' }}</div>
        <div class="dm-stat-valor dm-mono {{ $saldo > 0.009 ? 'dm-texto-ambar' : ($saldo < -0.009 ? 'dm-texto-lima' : '') }}">{{ $bs(abs($saldo)) }}</div>
        @if ($saldo > 0.009 && ! $orden->estaCerrada() && PagoResource::canCreate())
            <a class="dm-stat-enlace" href="{{ PagosRelationManager::urlNuevoCobro($orden) }}">Registrar cobro →</a>
        @endif
    </div>
</div>
