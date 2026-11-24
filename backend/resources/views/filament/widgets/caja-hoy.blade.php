@php
    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
@endphp

<x-filament-widgets::widget>
    <section class="dm-panel">
        <div class="dm-panel-cabecera">
            <h2 class="dm-panel-titulo">Caja de hoy</h2>
            @if ($modo === 'turno' && $abierta)
                <span class="dm-estado-busqueda dm-estado-busqueda--ok"><span class="dm-dot dm-dot--lima" aria-hidden="true"></span>Abierta</span>
            @endif
        </div>

        @if ($modo === 'cerrada')
            <p class="dm-ingreso-problema">No tienes una caja abierta. Ábrela para cobrar en efectivo y registrar gastos desde caja.</p>
            <a class="dm-btn dm-btn--secundario dm-btn--bloque" href="{{ $url }}">Ir a la caja del día</a>
        @else
            <div>
                <div class="dm-ingreso-etiqueta">{{ $modo === 'taller' ? 'Neto del día en el taller' : 'Neto de tu turno' }}</div>
                <div class="dm-cuenta-saldo dm-mono">{{ $bs($neto) }}</div>
            </div>

            <dl class="dm-cuenta-lineas dm-cuenta-lineas--separada">
                @foreach ($porMetodo as $nombre => $valor)
                    <div>
                        <dt>{{ $nombre }}</dt>
                        <dd class="dm-mono">{{ number_format($valor, 2, ',', '.') }}</dd>
                    </div>
                @endforeach
                <div>
                    <dt>Egresos</dt>
                    <dd class="dm-mono {{ $egresos > 0 ? 'dm-texto-rojo' : '' }}">− {{ number_format($egresos, 2, ',', '.') }}</dd>
                </div>
            </dl>

            <a class="dm-panel-enlace" href="{{ $url }}">Ver la caja del día →</a>
        @endif
    </section>
</x-filament-widgets::widget>
