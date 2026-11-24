@php
    use App\Models\CajaCierre;

    $bs = fn (float $n) => 'Bs '.number_format($n, 2, ',', '.');
    $iniciales = $cliente
        ? collect(preg_split('/\s+/', trim($cliente->nombre)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : null;

    $saldoCliente = $cliente?->saldoPendiente() ?? 0;

    // Base del "tras este cobro": la orden elegida o, si no hay, la cuenta completa del cliente.
    $base = $orden ? $orden->saldoPendiente() : $saldoCliente;
    $despues = $base - $monto;

    $lineas = collect();
    if ($orden) {
        $lineas->push(['texto' => $orden->codigo.' · total aprobado', 'valor' => $bs($orden->totalAprobado()), 'clase' => '']);
        foreach ($orden->pagos()->orderBy('fecha')->get() as $pago) {
            $lineas->push([
                'texto' => ucfirst(str_replace('_', ' ', $pago->tipo)).' '.$pago->fecha?->format('d/m').' ('.($pago->metodo === 'qr' ? 'QR' : $pago->metodo).')',
                'valor' => '− '.number_format($pago->monto, 2, ',', '.'),
                'clase' => 'dm-texto-lima',
            ]);
        }
    } elseif ($cliente) {
        foreach ($cliente->ordenesTrabajo()->conSaldo()->orderByDesc('fecha_ingreso')->get() as $o) {
            if (abs($o->saldoPendiente()) > 0.009) {
                $lineas->push(['texto' => $o->codigo, 'valor' => $bs($o->saldoPendiente()), 'clase' => $o->saldoPendiente() > 0 ? 'dm-texto-ambar' : 'dm-texto-lima']);
            }
        }
    }

    $caja = CajaCierre::abiertaDe((int) auth()->id());
@endphp

<aside class="dm-cuenta">
    @if (! $cliente)
        <div class="dm-cuenta-vacio">
            <div class="dm-cuenta-vacio-titulo">Estado de cuenta</div>
            Elige un cliente para ver lo que debe y lo que ya pagó.
        </div>
    @else
        <div class="dm-cuenta-cabecera">
            <span class="dm-avatar dm-avatar--md dm-tarjeta-avatar" aria-hidden="true">{{ $iniciales }}</span>
            <div>
                <div class="dm-tarjeta-titulo">{{ $cliente->nombre }}</div>
                <div class="dm-celda-sub">Estado de cuenta</div>
            </div>
        </div>

        <div class="dm-cuenta-bloque">
            <div class="dm-cuenta-etiqueta {{ $saldoCliente > 0.009 ? 'dm-cuenta-etiqueta--ambar' : '' }}">
                {{ $saldoCliente < -0.009 ? 'Saldo a favor' : 'Saldo pendiente' }}
            </div>
            <div class="dm-cuenta-saldo dm-mono {{ $saldoCliente > 0.009 ? 'dm-texto-ambar' : ($saldoCliente < -0.009 ? 'dm-texto-lima' : '') }}">
                {{ $bs(abs($saldoCliente)) }}
            </div>

            @if ($lineas->isNotEmpty())
                <dl class="dm-cuenta-lineas">
                    @foreach ($lineas as $linea)
                        <div>
                            <dt>{{ $linea['texto'] }}</dt>
                            <dd class="dm-mono {{ $linea['clase'] }}">{{ $linea['valor'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>

        <div class="dm-cuenta-despues">
            <span>Tras este cobro</span>
            @if ($monto <= 0)
                <span class="dm-mono dm-cuenta-despues-valor dm-texto-muted">—</span>
            @elseif ($despues < -0.009)
                <span class="dm-mono dm-cuenta-despues-valor dm-texto-lima">{{ $bs(abs($despues)) }} a favor</span>
            @elseif ($despues <= 0.009)
                <span class="dm-mono dm-cuenta-despues-valor dm-texto-lima">Bs 0,00 · al día</span>
            @else
                <span class="dm-mono dm-cuenta-despues-valor dm-texto-ambar">{{ $bs($despues) }} por cobrar</span>
            @endif
        </div>

        <div class="dm-cuenta-pie">
            @if ($metodo === 'efectivo')
                @if ($caja)
                    <span class="dm-dot dm-dot--lima" aria-hidden="true"></span>
                    Entra a tu caja abierta (efectivo esperado hoy: <span class="dm-mono">{{ $bs($caja->efectivoEsperado()) }}</span>).
                @else
                    <span class="dm-dot dm-dot--ambar" aria-hidden="true"></span>
                    No tienes una caja abierta. Ábrela antes de cobrar en efectivo.
                @endif
            @elseif (in_array($metodo, ['qr', 'tarjeta'], true))
                Este cobro no entra a la caja de efectivo.
            @else
                Elige el método de pago para ver dónde se registra.
            @endif
        </div>
    @endif
</aside>
