@extends('pdf.layout')

@section('meta')
    Comprobante de reparto N.° {{ $reparto->id }}<br>
    Generado por {{ $reparto->generadoPor?->nombre ?? '—' }} el {{ $reparto->generado_at?->format('d/m/Y H:i') }}
@endsection

@section('content')
    <h1>Reparto de utilidades</h1>
    <p class="muted">
        Período: {{ \Illuminate\Support\Carbon::parse($reparto->periodo_inicio)->format('d/m/Y') }}
        – {{ \Illuminate\Support\Carbon::parse($reparto->periodo_fin)->format('d/m/Y') }}
    </p>

    <table class="data">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Ingresos</td>
                <td class="num">Bs {{ number_format($reparto->ingresos_total, 2) }}</td>
            </tr>
            <tr>
                <td>Costos directos</td>
                <td class="num">Bs {{ number_format($reparto->costos_directos_total, 2) }}</td>
            </tr>
            <tr>
                <td>Gastos</td>
                <td class="num">Bs {{ number_format($reparto->gastos_total, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Utilidad neta a repartir</td>
                <td class="num">Bs {{ number_format($reparto->utilidad_neta, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="data" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Socio</th>
                <th class="num">Porcentaje aplicado</th>
                <th class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reparto->detalle as $detalle)
                <tr>
                    <td>{{ $detalle->socio?->nombre ?? '—' }}</td>
                    <td class="num">{{ number_format($detalle->porcentaje_aplicado, 2) }}%</td>
                    <td class="num">Bs {{ number_format($detalle->monto, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
