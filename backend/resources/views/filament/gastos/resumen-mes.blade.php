@php
    use App\Filament\Resources\GastoEgresos\GastoEgresoResource;
    use App\Models\GastoEgreso;

    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');

    // Solo los gastos que este usuario puede ver (el cajero, los suyos).
    $delMes = GastoEgresoResource::getEloquentQuery()
        ->whereYear('fecha', now()->year)
        ->whereMonth('fecha', now()->month)
        ->selectRaw('rubro, SUM(monto) as total')
        ->groupBy('rubro')
        ->pluck('total', 'rubro')
        ->map(fn ($t) => (float) $t)
        ->sortDesc();

    $total = $delMes->sum();
    $mayor = max($delMes->max() ?: 0, 1);
    $colores = ['var(--dm-lima)', 'var(--dm-azul)', 'var(--dm-violeta)', 'var(--dm-ambar)', 'var(--dm-muted)'];
@endphp

<section class="dm-panel">
    <div>
        <div class="dm-ingreso-etiqueta">Gastos de {{ now()->translatedFormat('F') }}</div>
        <div class="dm-cuenta-saldo dm-mono">{{ $bs($total) }}</div>
        @if ($monto > 0)
            <div class="dm-celda-sub">Con este: <span class="dm-mono">{{ $bs($total + $monto) }}</span></div>
        @endif
    </div>

    @if ($delMes->isNotEmpty())
        <div class="dm-resumen-rubros">
            @foreach ($delMes->take(6) as $clave => $valor)
                <div @class(['dm-resumen-rubro', 'dm-resumen-rubro--actual' => $clave === $rubro])>
                    <div class="dm-resumen-rubro-fila">
                        <span>{{ GastoEgreso::RUBROS[$clave]['nombre'] ?? ucfirst($clave) }}</span>
                        <span class="dm-mono">{{ $bs($valor) }}</span>
                    </div>
                    <div class="dm-medidor"><span style="width: {{ round($valor / $mayor * 100) }}%; background: {{ $colores[$loop->index % count($colores)] }}"></span></div>
                </div>
            @endforeach
        </div>
    @else
        <p class="dm-ingreso-problema">Todavía no hay gastos este mes.</p>
    @endif
</section>
