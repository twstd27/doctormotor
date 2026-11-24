@php
    use App\Support\EstadoOrden;
    use Illuminate\Support\Carbon;

    $eventos = collect();

    foreach ($orden->estadosHistorial()->with('user:id,nombre')->get() as $cambio) {
        $eventos->push([
            'cuando' => $cambio->created_at,
            'color' => EstadoOrden::color($cambio->estado_nuevo),
            'texto' => $cambio->estado_anterior === null
                ? 'Vehículo recepcionado'
                : 'Pasó a <strong>'.e(EstadoOrden::etiqueta($cambio->estado_nuevo)).'</strong>',
            'quien' => $cambio->user?->nombre,
        ]);
    }

    foreach ($orden->pagos()->with('cajero:id,nombre')->get() as $pago) {
        $eventos->push([
            'cuando' => $pago->fecha,
            'color' => 'info',
            'texto' => 'Cobro de '.e(str_replace('_', ' ', $pago->tipo)).' · <span class="dm-mono">Bs '.number_format($pago->monto, 2, ',', '.').'</span> '.e($pago->metodo === 'qr' ? 'QR' : $pago->metodo),
            'quien' => $pago->cajero?->nombre,
        ]);
    }

    foreach ($orden->presupuestos()->where('estado', 'aprobado')->whereNotNull('respondido_at')->get() as $presupuesto) {
        $eventos->push([
            'cuando' => $presupuesto->respondido_at,
            'color' => 'warning',
            'texto' => 'El cliente aprobó el presupuesto de <span class="dm-mono">Bs '.number_format($presupuesto->total, 2, ',', '.').'</span>',
            'quien' => null,
        ]);
    }

    $eventos = $eventos->sortByDesc('cuando')->take(8);

    $cuando = function ($fecha) {
        $fecha = Carbon::parse($fecha);

        return ($fecha->isToday() ? 'hoy ' : $fecha->format('d/m ')).$fecha->format('H:i');
    };
@endphp

<section class="dm-panel">
    <h2 class="dm-panel-titulo">Actividad</h2>

    @if ($eventos->isEmpty())
        <p class="dm-ingreso-problema">Todavía no hay movimientos.</p>
    @else
        <ol class="dm-actividad">
            @foreach ($eventos as $evento)
                <li>
                    <span class="dm-actividad-punto dm-actividad-punto--{{ $evento['color'] }}" aria-hidden="true"></span>
                    <div>
                        <div class="dm-actividad-texto">{!! $evento['texto'] !!}</div>
                        <div class="dm-actividad-meta">{{ $evento['quien'] ? $evento['quien'].' · ' : '' }}{{ $cuando($evento['cuando']) }}</div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
