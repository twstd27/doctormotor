@extends('pdf.layout')

@section('meta')
    Recibo N.° {{ $pago->id }}<br>
    {{ $pago->fecha->format('d/m/Y H:i') }}
@endsection

@section('content')
    <h1>Recibo de pago</h1>
    <p class="muted">Cliente: {{ $pago->cliente->nombre }}</p>

    <table class="data">
        <tbody>
            <tr><td>Concepto</td><td class="num">{{ ucfirst(str_replace('_', ' ', $pago->tipo)) }}</td></tr>
            @if ($pago->ordenTrabajo)
                <tr><td>Orden de trabajo</td><td class="num">{{ $pago->ordenTrabajo->codigo }}</td></tr>
            @endif
            <tr><td>Método de pago</td><td class="num">{{ strtoupper($pago->metodo) }}</td></tr>
            @if ($pago->referencia_externa)
                <tr><td>Referencia</td><td class="num">{{ $pago->referencia_externa }}</td></tr>
            @endif
        </tbody>
    </table>

    @if ($pago->detalle_tipo === 'detallado' && $pago->detalle_items)
        <table class="data">
            <thead><tr><th colspan="3">Detalle del servicio</th></tr></thead>
            <tbody>
                @foreach ($pago->detalle_items as $item)
                    <tr>
                        <td>{{ $item['descripcion'] }}</td>
                        <td class="num">{{ $item['cantidad'] }} × Bs {{ number_format($item['precio_unitario'], 2) }}</td>
                        <td class="num">Bs {{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($pago->detalle_servicio)
        <p class="muted">Detalle del servicio: {{ $pago->detalle_servicio }}</p>
    @endif

    <table class="data">
        <tbody>
            <tr class="total-row"><td>Monto</td><td class="num">Bs {{ number_format($pago->monto, 2) }}</td></tr>
        </tbody>
    </table>
@endsection
