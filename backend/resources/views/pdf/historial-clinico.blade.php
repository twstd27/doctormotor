@extends('pdf.layout')

@section('meta')
    Historial clínico automotriz<br>
    {{ now()->format('d/m/Y') }}
@endsection

@section('content')
    <h1>{{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->anio }})</h1>
    <p class="muted">
        Placa: {{ $vehiculo->placa }} · Color: {{ $vehiculo->color }} ·
        Propietario: {{ $vehiculo->cliente->nombre }}
    </p>

    @forelse ($ordenes as $ot)
        <table class="data">
            <thead>
                <tr>
                    <th colspan="2">{{ $ot->codigo }} — {{ $ot->fecha_ingreso->format('d/m/Y') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr><td style="width:140px">Motivo de ingreso</td><td>{{ $ot->descripcion_problema }}</td></tr>
                <tr><td>Kilometraje</td><td>{{ number_format($ot->kilometraje_ingreso) }} km</td></tr>
                <tr><td>Estado final</td><td>{{ ucfirst(str_replace('_', ' ', $ot->estado)) }}</td></tr>
                @if ($ot->fecha_entrega_real)
                    <tr><td>Entregado</td><td>{{ $ot->fecha_entrega_real->format('d/m/Y') }}</td></tr>
                @endif
            </tbody>
        </table>
    @empty
        <p class="muted">Este vehículo todavía no tiene órdenes de trabajo registradas.</p>
    @endforelse
@endsection
