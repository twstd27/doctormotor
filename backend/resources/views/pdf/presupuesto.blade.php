@extends('pdf.layout')

@section('meta')
    Presupuesto N.° {{ $presupuesto->id }} · v{{ $presupuesto->version }}<br>
    {{ $presupuesto->created_at->format('d/m/Y') }}
@endsection

@section('content')
    <h1>Presupuesto — {{ $presupuesto->ordenTrabajo->codigo }}</h1>
    <p class="muted">
        Cliente: {{ $presupuesto->ordenTrabajo->cliente->nombre }} ·
        Vehículo: {{ $presupuesto->ordenTrabajo->vehiculo->marca }} {{ $presupuesto->ordenTrabajo->vehiculo->modelo }}
        ({{ $presupuesto->ordenTrabajo->vehiculo->placa }})
    </p>

    <table class="data">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Tipo</th>
                <th class="num">Cant.</th>
                <th class="num">P. unitario</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($presupuesto->items as $item)
                <tr>
                    <td>{{ $item->descripcion }} @if($item->es_adicional) <em>(adicional)</em> @endif</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->tipo)) }}</td>
                    <td class="num">{{ number_format($item->cantidad, 2) }}</td>
                    <td class="num">Bs {{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="num">Bs {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" style="text-align:right">Subtotal</td>
                <td class="num">Bs {{ number_format($presupuesto->subtotal, 2) }}</td>
            </tr>
            @if ($presupuesto->descuento > 0)
                <tr>
                    <td colspan="4" style="text-align:right">Descuento</td>
                    <td class="num">- Bs {{ number_format($presupuesto->descuento, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="4" style="text-align:right">Total</td>
                <td class="num">Bs {{ number_format($presupuesto->total, 2) }}</td>
            </tr>
        </tbody>
    </table>
@endsection
