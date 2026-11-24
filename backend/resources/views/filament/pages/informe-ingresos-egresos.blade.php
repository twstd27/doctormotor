<x-filament-panels::page>
    @php
        $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
        $num = fn (float $n) => number_format($n, 2, ',', '.');
        $ingresos = $this->totalIngresos();
        $costos = $this->totalCostosDirectos();
        $gastos = $this->totalGastos();
        $utilidad = $this->utilidad();
        $margen = $this->margenPorcentaje();
        $serie = $this->serie();
        $mayor = max($serie->max(fn ($s) => max($s['ingresos'], $s['egresos'])), 1);
        $detalle = $this->detalleDiario();
        $rangos = [['hoy', 'Hoy'], ['7dias', '7 días'], ['estemes', 'Este mes'], ['mesanterior', 'Mes anterior'], ['ano', 'Año']];
        $colores = ['var(--dm-lima)', 'var(--dm-azul)', 'var(--dm-violeta)', 'var(--dm-ambar)', 'var(--dm-muted)'];

        // Bloque con barra proporcional y lista: método de pago, tipo de costo y rubro de gasto.
        $bloques = [
            ['Ingresos por método', $this->ingresosPorMetodo()],
            ['Costos directos por tipo', $this->costosPorTipo()],
            ['Gastos por rubro', $this->gastosPorRubro()],
        ];
    @endphp

    <div class="dm-pila">
        {{-- Rango --}}
        <div class="dm-filtros dm-filtros--rango">
            @foreach ($rangos as [$tipo, $etiqueta])
                <button type="button" wire:click="aplicarRango('{{ $tipo }}')"
                        @class(['dm-filtro', 'dm-filtro--activo' => $this->rangoActivo() === $tipo])>{{ $etiqueta }}</button>
            @endforeach
            <span class="dm-rango-fechas">
                <x-filament::input.wrapper class="dm-fecha-input">
                    <x-filament::input type="date" wire:model.live="desde" aria-label="Desde" />
                </x-filament::input.wrapper>
                <span class="dm-celda-sub">a</span>
                <x-filament::input.wrapper class="dm-fecha-input">
                    <x-filament::input type="date" wire:model.live="hasta" aria-label="Hasta" />
                </x-filament::input.wrapper>
            </span>
        </div>

        {{-- Cuatro números --}}
        <div class="dm-stats dm-stats--4">
            <div class="dm-stat">
                <div class="dm-stat-etiqueta">Ingresos</div>
                <div class="dm-stat-valor dm-mono">{{ $bs($ingresos) }}</div>
                <div class="dm-celda-sub">Todo lo cobrado en el período</div>
            </div>
            <div class="dm-stat">
                <div class="dm-stat-etiqueta">Costos directos</div>
                <div class="dm-stat-valor dm-mono">{{ $bs($costos) }}</div>
                <div class="dm-celda-sub">Repuestos y mano de obra de las órdenes</div>
            </div>
            <div class="dm-stat">
                <div class="dm-stat-etiqueta">Gastos</div>
                <div class="dm-stat-valor dm-mono">{{ $bs($gastos) }}</div>
                <div class="dm-celda-sub">Alquiler, sueldos, servicios…</div>
            </div>
            <div class="dm-stat {{ $utilidad >= 0 ? 'dm-stat--efectivo' : 'dm-stat--falla' }}">
                <div class="dm-stat-etiqueta">{{ $utilidad >= 0 ? 'Utilidad del período' : 'Pérdida del período' }}</div>
                <div class="dm-stat-valor dm-mono {{ $utilidad >= 0 ? 'dm-texto-lima' : 'dm-texto-rojo' }}">{{ $bs(abs($utilidad)) }}</div>
                <div class="dm-celda-sub">{{ $margen !== null ? 'Margen '.number_format($margen, 1, ',', '.').'%' : 'Sin ingresos en el período' }}</div>
            </div>
        </div>

        {{-- Por día --}}
        <section class="dm-panel">
            <div class="dm-panel-cabecera">
                <h2 class="dm-panel-titulo">{{ $serie->count() > 0 && \Illuminate\Support\Carbon::parse($desde)->diffInDays($hasta) > 62 ? 'Por mes' : 'Por día' }}</h2>
                <div class="dm-barras-leyenda">
                    <span><i class="dm-cuadrito" style="background: var(--dm-lima)"></i>Ingresos</span>
                    <span><i class="dm-cuadrito" style="background: #5b6470"></i>Egresos (costos y gastos)</span>
                </div>
            </div>
            <div class="dm-grafico" role="img" aria-label="Gráfico de barras de ingresos y egresos del período">
                <div class="dm-grafico-barras" style="grid-template-columns: repeat({{ max($serie->count(), 1) }}, minmax(0, 1fr))">
                    @foreach ($serie as $punto)
                        <div class="dm-barras-grupo" title="{{ $punto['titulo'] }}: ingresos {{ $bs($punto['ingresos']) }}, egresos {{ $bs($punto['egresos']) }}">
                            <span class="dm-barra dm-barra--ingresos" style="height: {{ max(round($punto['ingresos'] / $mayor * 100), $punto['ingresos'] > 0 ? 2 : 0) }}%"></span>
                            <span class="dm-barra dm-barra--egresos" style="height: {{ max(round($punto['egresos'] / $mayor * 100), $punto['egresos'] > 0 ? 2 : 0) }}%"></span>
                        </div>
                    @endforeach
                </div>
                <div class="dm-grafico-etiquetas" aria-hidden="true" style="grid-template-columns: repeat({{ max($serie->count(), 1) }}, minmax(0, 1fr))">
                    @foreach ($serie as $punto)
                        <span class="dm-mono">{{ $punto['etiqueta'] }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Tres desgloses --}}
        <div class="dm-tres">
            @foreach ($bloques as [$titulo, $datos])
                @php($total = max(array_sum($datos), 0.01))
                <section class="dm-panel">
                    <h2 class="dm-panel-titulo">{{ $titulo }}</h2>
                    @if (array_sum($datos) > 0)
                        <div class="dm-medidor dm-medidor--alto">
                            @foreach (array_values($datos) as $i => $valor)
                                <span style="width: {{ round($valor / $total * 100, 1) }}%; background: {{ $colores[$i % count($colores)] }}"></span>
                            @endforeach
                        </div>
                        <dl class="dm-cuenta-lineas">
                            @foreach (array_slice($datos, 0, 6, true) as $nombre => $valor)
                                <div>
                                    <dt><i class="dm-cuadrito" style="background: {{ $colores[$loop->index % count($colores)] }}"></i> {{ $nombre }}</dt>
                                    <dd class="dm-mono">{{ $num($valor) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="dm-celda-sub">Sin movimientos en el período.</p>
                    @endif
                </section>
            @endforeach
        </div>

        {{-- Detalle por día --}}
        <section class="dm-tabla-panel">
            <div class="dm-tabla-cabecera">
                <h2 class="dm-panel-titulo">Detalle por día</h2>
                <span class="dm-celda-sub">{{ count($detalle) }} {{ count($detalle) === 1 ? 'día' : 'días' }}</span>
            </div>
            @if (count($detalle) > 0)
                <div class="dm-tabla-scroll">
                    <table class="dm-tabla">
                        <thead>
                            <tr>
                                <th>Día</th>
                                <th class="dm-tabla-der">Ingresos</th>
                                <th class="dm-tabla-der">Costos directos</th>
                                <th class="dm-tabla-der">Gastos</th>
                                <th class="dm-tabla-der">Neto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detalle as $fila)
                                <tr>
                                    <td>{{ ucfirst(\Illuminate\Support\Carbon::parse($fila['fecha'])->translatedFormat('D d/m/Y')) }}</td>
                                    <td class="dm-tabla-der dm-mono">{{ $num($fila['ingresos']) }}</td>
                                    <td class="dm-tabla-der dm-mono dm-texto-muted">{{ $num($fila['costos']) }}</td>
                                    <td class="dm-tabla-der dm-mono dm-texto-muted">{{ $num($fila['gastos']) }}</td>
                                    <td class="dm-tabla-der dm-mono dm-tabla-monto {{ $fila['neto'] >= 0 ? 'dm-texto-lima' : 'dm-texto-rojo' }}">{{ $num($fila['neto']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="dm-tabla-total">Total</td>
                                <td class="dm-tabla-der dm-mono dm-tabla-total">{{ $num($ingresos) }}</td>
                                <td class="dm-tabla-der dm-mono dm-tabla-total">{{ $num($costos) }}</td>
                                <td class="dm-tabla-der dm-mono dm-tabla-total">{{ $num($gastos) }}</td>
                                <td class="dm-tabla-der dm-mono dm-tabla-total {{ $utilidad >= 0 ? 'dm-texto-lima' : 'dm-texto-rojo' }}">{{ $num($utilidad) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="dm-tabla-vacia">Sin movimientos en este rango de fechas.
                    <button type="button" wire:click="aplicarRango('estemes')" class="dm-panel-enlace">Ver este mes</button>
                </p>
            @endif
        </section>
    </div>
</x-filament-panels::page>
