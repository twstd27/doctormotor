<x-filament-panels::page>
    @php
        $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
        $num = fn (float $n) => number_format($n, 2, ',', '.');
        $suma = $this->sumaPorcentajes();
        $sumaOk = $suma === 100.0;
        $ingresos = $this->totalIngresos();
        $costos = $this->totalCostosDirectos();
        $gastos = $this->totalGastos();
        $utilidad = $ingresos - $costos - $gastos;
        $repartos = $this->repartos();
        [$abiertas, $porCobrar] = $this->abiertasPorCobrar();
        $colores = ['var(--dm-lima)', 'var(--dm-azul)', 'var(--dm-violeta)', 'var(--dm-ambar)', 'var(--dm-muted)'];
        $rangos = [['estemes', 'Este mes'], ['mesanterior', 'Mes anterior'], ['7dias', '7 días'], ['ano', 'Año']];
        $inicio = \Illuminate\Support\Carbon::parse($periodoInicio);
        $etiquetaPeriodo = $inicio->isSameMonth(\Illuminate\Support\Carbon::parse($periodoFin)) && $inicio->day === 1
            ? $inicio->translatedFormat('F')
            : 'el período';
    @endphp

    <div class="dm-pila">
        <div class="dm-reparto-columnas">
            {{-- Socios y porcentajes --}}
            <section class="dm-panel">
                <div class="dm-panel-cabecera">
                    <div>
                        <h2 class="dm-panel-titulo">Socios y porcentajes</h2>
                        <p class="dm-celda-sub">Deben sumar exactamente 100 %.</p>
                    </div>
                </div>

                @if (count($reglas) === 0)
                    <p class="dm-ingreso-problema">Todavía no hay socios activos. Añade el primero con «Añadir socio».</p>
                @else
                    <div class="dm-medidor dm-medidor--alto dm-medidor--grueso" aria-hidden="true">
                        @foreach ($reglas as $i => $regla)
                            <span style="width: {{ min(max((float) $regla['porcentaje'], 0), 100) }}%; background: {{ $colores[$i % count($colores)] }}"></span>
                        @endforeach
                    </div>

                    <div class="dm-socios">
                        @foreach ($reglas as $i => $regla)
                            <div class="dm-socio">
                                <span class="dm-avatar dm-avatar--md" style="background: color-mix(in srgb, {{ $colores[$i % count($colores)] }} 18%, transparent); color: {{ $colores[$i % count($colores)] }}" aria-hidden="true">{{ \App\Filament\Pages\RepartoUtilidades::iniciales($regla['nombre']) }}</span>
                                <div class="dm-socio-nombre">
                                    <div class="dm-celda-titulo">{{ $regla['nombre'] }}</div>
                                    <div class="dm-celda-sub">Socio</div>
                                </div>
                                <x-filament::input.wrapper suffix="%" class="dm-socio-pct">
                                    <x-filament::input type="number" step="0.01" min="0" max="100"
                                        wire:model.live="reglas.{{ $i }}.porcentaje" aria-label="Porcentaje de {{ $regla['nombre'] }}" />
                                </x-filament::input.wrapper>
                                <div class="dm-socio-monto">
                                    <div class="dm-mono dm-celda-titulo">{{ $num($utilidad * ((float) $regla['porcentaje']) / 100) }}</div>
                                    <div class="dm-celda-sub">{{ $etiquetaPeriodo === 'el período' ? 'del período' : 'de '.$etiquetaPeriodo }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="dm-socios-pie">
                        <span @class(['dm-socios-suma', 'dm-texto-lima' => $sumaOk, 'dm-texto-ambar' => ! $sumaOk])>
                            @php($diferencia = rtrim(rtrim(number_format(abs(100 - $suma), 2, ',', '.'), '0'), ','))
                            {{ $sumaOk ? 'Suma 100 %: listo para guardar.' : 'Suman '.rtrim(rtrim(number_format($suma, 2, ',', '.'), '0'), ',').' %: '.($suma < 100 ? 'faltan '.$diferencia.' % por asignar' : 'sobran '.$diferencia.' %') }}
                        </span>
                        <x-filament::button wire:click="guardarReglas" :disabled="! $sumaOk" color="gray">Guardar porcentajes</x-filament::button>
                    </div>
                @endif
            </section>

            {{-- Generar reparto --}}
            <section class="dm-panel" id="generar-reparto">
                <div>
                    <h2 class="dm-panel-titulo">Generar reparto</h2>
                    <p class="dm-celda-sub">Ingresos − costos directos − gastos, repartido según los porcentajes.</p>
                </div>

                <div class="dm-filtros dm-filtros--rango">
                    @foreach ($rangos as [$tipo, $etiqueta])
                        <button type="button" wire:click="aplicarRango('{{ $tipo }}')"
                                @class(['dm-filtro', 'dm-filtro--activo' => $this->rangoActivo() === $tipo])>{{ $etiqueta }}</button>
                    @endforeach
                </div>
                <div class="dm-rango-fechas dm-rango-fechas--bloque">
                    <x-filament::input.wrapper class="dm-fecha-input">
                        <x-filament::input type="date" wire:model.live="periodoInicio" aria-label="Desde" />
                    </x-filament::input.wrapper>
                    <span class="dm-celda-sub">a</span>
                    <x-filament::input.wrapper class="dm-fecha-input">
                        <x-filament::input type="date" wire:model.live="periodoFin" aria-label="Hasta" />
                    </x-filament::input.wrapper>
                </div>

                <div class="dm-cascada">
                    <div><span>Ingresos</span><span class="dm-mono">{{ $num($ingresos) }}</span></div>
                    <div><span>− Costos directos</span><span class="dm-mono dm-texto-rojo">{{ $num($costos) }}</span></div>
                    <div><span>− Gastos</span><span class="dm-mono dm-texto-rojo">{{ $num($gastos) }}</span></div>
                    <div class="dm-cascada-total">
                        <span>{{ $utilidad >= 0 ? 'Utilidad neta a repartir' : 'Pérdida del período (no se reparte)' }}</span>
                        <span class="dm-mono {{ $utilidad >= 0 ? 'dm-texto-lima' : 'dm-texto-rojo' }}">{{ $bs(abs($utilidad)) }}</span>
                    </div>
                </div>

                @if ($abiertas > 0)
                    <div class="dm-aviso dm-aviso--ambar">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
                        <span>Hay {{ $abiertas }} {{ $abiertas === 1 ? 'orden abierta' : 'órdenes abiertas' }}@if ($porCobrar > 0.009) con <span class="dm-mono">{{ $bs($porCobrar) }}</span> por cobrar @endif que no entran en este cálculo.</span>
                    </div>
                @endif

                <x-filament::button wire:click="generarReparto" size="lg" class="dm-btn-ancho">Generar reparto</x-filament::button>
            </section>
        </div>

        {{-- Historial --}}
        <section class="dm-panel">
            <div class="dm-panel-cabecera">
                <h2 class="dm-panel-titulo">Historial de repartos</h2>
                <span class="dm-celda-sub">{{ $repartos->count() }} {{ $repartos->count() === 1 ? 'reparto' : 'repartos' }}</span>
            </div>

            @if ($repartos->isEmpty())
                <p class="dm-ingreso-problema">Todavía no se generó ningún reparto. <a class="dm-panel-enlace" href="#generar-reparto">Genera el primero</a>.</p>
            @else
                <div class="dm-historial">
                    @foreach ($repartos as $reparto)
                        <div x-data="{ abierto: false }" class="dm-historial-item">
                            <button type="button" x-on:click="abierto = ! abierto" class="dm-historial-cabecera">
                                <div>
                                    <div class="dm-celda-titulo">{{ \Illuminate\Support\Carbon::parse($reparto->periodo_inicio)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($reparto->periodo_fin)->format('d/m/Y') }}</div>
                                    <div class="dm-celda-sub">Generado por {{ $reparto->generadoPor?->nombre ?? '—' }} el {{ $reparto->generado_at?->format('d/m/Y · H:i') }}</div>
                                </div>
                                <div class="dm-historial-resumen">
                                    <span class="dm-contador dm-contador--gris">{{ $reparto->detalle->count() }} {{ $reparto->detalle->count() === 1 ? 'socio' : 'socios' }}</span>
                                    <span class="dm-mono dm-celda-titulo {{ $reparto->utilidad_neta >= 0 ? 'dm-texto-lima' : 'dm-texto-rojo' }}">{{ $bs((float) $reparto->utilidad_neta) }}</span>
                                </div>
                            </button>

                            <div x-show="abierto" x-cloak class="dm-historial-detalle">
                                <dl class="dm-cuenta-lineas">
                                    @foreach ($reparto->detalle as $detalle)
                                        <div>
                                            <dt>{{ $detalle->socio?->nombre ?? '—' }} <span class="dm-mono">· {{ rtrim(rtrim(number_format($detalle->porcentaje_aplicado, 2, ',', '.'), '0'), ',') }} %</span></dt>
                                            <dd class="dm-mono">{{ $num((float) $detalle->monto) }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                                <div class="dm-historial-acciones">
                                    <a class="dm-btn dm-btn--secundario" href="{{ route('admin-pdf.reparto-utilidades.comprobante', $reparto) }}" target="_blank">Descargar comprobante</a>
                                    <button type="button" class="dm-btn dm-btn--peligro"
                                            x-on:click="if (confirm('¿Anular este reparto? Esta acción no se puede deshacer.')) $wire.anularReparto({{ $reparto->id }})">Anular reparto</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
