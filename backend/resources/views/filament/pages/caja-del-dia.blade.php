@php
    use App\Filament\Resources\CajaCierres\CajaCierreResource;

    $caja = $this->caja();
    $movimientos = $this->movimientos($caja);
    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');

    $ingresos = (float) $caja?->pagos()->sum('monto');
    $cantIngresos = (int) $caja?->pagos()->count();
    $egresos = $caja?->gastosEnEfectivo() ?? 0;
    $cantEgresos = (int) $caja?->gastos()->where('origen', 'caja')->count();
    $efectivo = $caja?->cobrosEnEfectivo() ?? 0;
    $porMetodo = [
        'efectivo' => $efectivo,
        'qr' => (float) $caja?->pagos()->where('metodo', 'qr')->sum('monto'),
        'tarjeta' => (float) $caja?->pagos()->where('metodo', 'tarjeta')->sum('monto'),
    ];
    $totalMetodos = max(array_sum($porMetodo), 0.01);

    $cuentaPorMetodo = $movimientos->groupBy('metodo')->map->count();
    $visibles = $this->filtro === 'todos' ? $movimientos : $movimientos->where('metodo', $this->filtro);
    $filtros = ['todos' => 'Todos', 'efectivo' => 'Efectivo', 'qr' => 'QR', 'tarjeta' => 'Tarjeta'];
    $nombreMetodo = ['efectivo' => 'Efectivo', 'qr' => 'QR', 'tarjeta' => 'Tarjeta'];
    $colorMetodo = ['efectivo' => 'var(--dm-lima)', 'qr' => 'var(--dm-azul)', 'tarjeta' => 'var(--dm-violeta)'];
    $cierres = $this->cierresAnteriores();
@endphp

<x-filament-panels::page>
<div class="dm-caja-pagina">
    @if ($caja)
        <div class="dm-stats dm-stats--4">
            <div class="dm-stat">
                <div class="dm-stat-etiqueta">Apertura</div>
                <div class="dm-stat-valor dm-mono">{{ $bs((float) $caja->monto_apertura) }}</div>
                <div class="dm-celda-sub">Fondo de cambio</div>
            </div>
            <div class="dm-stat">
                <div class="dm-stat-etiqueta">Ingresos</div>
                <div class="dm-stat-valor dm-mono dm-texto-lima">+ {{ $bs($ingresos) }}</div>
                <div class="dm-celda-sub">{{ $cantIngresos }} {{ $cantIngresos === 1 ? 'cobro' : 'cobros' }}</div>
            </div>
            <div class="dm-stat">
                <div class="dm-stat-etiqueta">Egresos</div>
                <div class="dm-stat-valor dm-mono {{ $egresos > 0 ? 'dm-texto-rojo' : '' }}">− {{ $bs($egresos) }}</div>
                <div class="dm-celda-sub">{{ $cantEgresos }} {{ $cantEgresos === 1 ? 'gasto' : 'gastos' }} en efectivo</div>
            </div>
            <div class="dm-stat dm-stat--efectivo">
                <div class="dm-stat-etiqueta">Efectivo que debe haber</div>
                <div class="dm-stat-valor dm-mono dm-texto-lima">{{ $bs($caja->efectivoEsperado()) }}</div>
                <div class="dm-celda-sub dm-mono">{{ number_format((float) $caja->monto_apertura, 0, ',', '.') }} + {{ number_format($efectivo, 0, ',', '.') }} efectivo − {{ number_format($egresos, 0, ',', '.') }}</div>
            </div>
        </div>
    @else
        <div class="dm-panel dm-caja-vacia">
            <div class="dm-panel-titulo">No tienes una caja abierta</div>
            <p class="dm-ingreso-problema">Abre tu turno con el fondo de cambio para poder cobrar en efectivo y registrar gastos desde caja.</p>
        </div>
    @endif

    <div class="dm-caja-division">
        @if ($caja)
            <section class="dm-tabla-panel">
                <div class="dm-tabla-cabecera">
                    <h2 class="dm-panel-titulo">Movimientos de hoy</h2>
                    <div class="dm-filtros">
                        @foreach ($filtros as $clave => $nombre)
                            <button type="button"
                                    wire:click="$set('filtro', '{{ $clave }}')"
                                    @class(['dm-filtro', 'dm-filtro--activo' => $this->filtro === $clave])>
                                {{ $nombre }} <span class="dm-mono">{{ $clave === 'todos' ? $movimientos->count() : ($cuentaPorMetodo[$clave] ?? 0) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="dm-tabla-scroll">
                    <table class="dm-tabla">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Concepto</th>
                                <th>Método</th>
                                <th>Por</th>
                                <th class="dm-tabla-der">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($visibles as $mov)
                                <tr>
                                    <td class="dm-mono dm-celda-sub">{{ $mov['cuando']?->format('H:i') }}</td>
                                    <td>
                                        <div class="dm-celda-titulo dm-celda-titulo--normal">{{ $mov['concepto'] }}</div>
                                        <div class="dm-celda-sub">{{ $mov['detalle'] }}</div>
                                    </td>
                                    <td><span class="dm-metodo dm-metodo--{{ $mov['metodo'] }}">{{ $nombreMetodo[$mov['metodo']] ?? $mov['metodo'] }}</span></td>
                                    <td class="dm-celda-sub">{{ $mov['por'] }}</td>
                                    <td class="dm-tabla-der dm-mono dm-tabla-monto {{ $mov['signo'] === '+' ? 'dm-texto-lima' : ($mov['signo'] === '−' ? 'dm-texto-rojo' : 'dm-texto-muted') }}">
                                        {{ $mov['signo'] }} {{ number_format($mov['monto'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="dm-tabla-vacia">No hay movimientos con ese filtro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <aside class="dm-caja-lateral">
            @if ($caja)
                <section class="dm-panel">
                    <h2 class="dm-panel-titulo">Hoy por método</h2>
                    <div class="dm-medidor dm-medidor--alto">
                        @foreach ($porMetodo as $metodo => $valor)
                            <span style="width: {{ round($valor / $totalMetodos * 100, 1) }}%; background: {{ $colorMetodo[$metodo] }}"></span>
                        @endforeach
                    </div>
                    <dl class="dm-cuenta-lineas">
                        @foreach ($porMetodo as $metodo => $valor)
                            <div>
                                <dt><i class="dm-cuadrito" style="background: {{ $colorMetodo[$metodo] }}"></i> {{ $nombreMetodo[$metodo] }}</dt>
                                <dd class="dm-mono">{{ number_format($valor, 2, ',', '.') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="dm-celda-sub">Solo el efectivo se cuenta al cerrar. QR y tarjeta se concilian con el banco.</p>
                </section>
            @endif

            <section class="dm-panel">
                <div class="dm-panel-cabecera">
                    <h2 class="dm-panel-titulo">Cierres anteriores</h2>
                    <a class="dm-panel-enlace" href="{{ CajaCierreResource::getUrl('index') }}">Ver todos</a>
                </div>
                @forelse ($cierres as $cierre)
                    @php($dif = (float) $cierre->diferencia)
                    <div class="dm-cierre">
                        <div>
                            <div class="dm-celda-titulo dm-celda-titulo--normal">{{ ucfirst($cierre->cerrado_at->translatedFormat('D d/m')) }}</div>
                            <div class="dm-celda-sub">{{ $cierre->cajero?->nombre }} · contado {{ $bs((float) $cierre->monto_contado) }}</div>
                        </div>
                        <span @class(['dm-diferencia', 'dm-mono', 'dm-diferencia--ok' => abs($dif) < 0.005, 'dm-diferencia--sobra' => $dif >= 0.005, 'dm-diferencia--falta' => $dif <= -0.005])>
                            {{ abs($dif) < 0.005 ? 'Cuadra' : ($dif > 0 ? '+' : '−').' Bs '.number_format(abs($dif), 2, ',', '.') }}
                        </span>
                    </div>
                @empty
                    <p class="dm-celda-sub">Todavía no hay cierres.</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
</x-filament-panels::page>
