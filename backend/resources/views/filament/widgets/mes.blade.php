@php
    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
@endphp

<x-filament-widgets::widget>
    <section class="dm-panel">
        <h2 class="dm-panel-titulo">{{ $mes }}</h2>

        <div>
            <div class="dm-ingreso-etiqueta">Utilidad del mes</div>
            <div class="dm-cuenta-saldo dm-mono {{ $utilidad >= 0 ? 'dm-texto-lima' : 'dm-texto-rojo' }}">{{ $bs($utilidad) }}</div>
            <div class="dm-celda-sub">
                Ingresos <span class="dm-mono">{{ $bs($ingresos) }}</span> · egresos <span class="dm-mono">{{ $bs($egresos) }}</span>
            </div>
        </div>

        <div class="dm-barras" role="img" aria-label="Ingresos y egresos por semana">
            @foreach ($semanas as $i => $semana)
                <div class="dm-barras-grupo" title="Semana {{ $i + 1 }}: ingresos {{ $bs($semana['ingresos']) }}, egresos {{ $bs($semana['egresos']) }}">
                    <span class="dm-barra dm-barra--ingresos" style="height: {{ max($semana['altoIngresos'], $semana['ingresos'] > 0 ? 4 : 0) }}%"></span>
                    <span class="dm-barra dm-barra--egresos" style="height: {{ max($semana['altoEgresos'], $semana['egresos'] > 0 ? 4 : 0) }}%"></span>
                </div>
            @endforeach
        </div>
        <div class="dm-barras-leyenda">
            <span><i class="dm-cuadrito" style="background: var(--dm-lima)"></i>Ingresos</span>
            <span><i class="dm-cuadrito" style="background: #4a525c"></i>Egresos</span>
        </div>
    </section>
</x-filament-widgets::widget>
