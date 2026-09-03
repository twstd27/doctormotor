<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 6px 10px; size: 80mm auto; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans Mono', monospace; font-size: 10px; color: #000; width: 76mm; }
        .center { text-align: center; }
        .name { font-size: 13px; font-weight: bold; }
        .sub { font-size: 9px; }
        hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        td { padding: 2px 0; }
        td.right { text-align: right; }
        .total { font-size: 13px; font-weight: bold; }
        .foot { text-align: center; font-size: 8px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="center">
        <div class="name">DOCTOR MOTOR</div>
        <div class="sub">MUSTANG'S GARAGE</div>
    </div>
    <hr>
    <table>
        <tr><td>Recibo</td><td class="right">#{{ $pago->id }}</td></tr>
        <tr><td>Fecha</td><td class="right">{{ $pago->fecha->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Cliente</td><td class="right">{{ $pago->cliente->nombre }}</td></tr>
        @if ($pago->ordenTrabajo)
            <tr><td>OT</td><td class="right">{{ $pago->ordenTrabajo->codigo }}</td></tr>
        @endif
        <tr><td>Concepto</td><td class="right">{{ ucfirst(str_replace('_', ' ', $pago->tipo)) }}</td></tr>
        <tr><td>Método</td><td class="right">{{ strtoupper($pago->metodo) }}</td></tr>
    </table>
    <hr>
    <table>
        <tr class="total"><td>TOTAL</td><td class="right">Bs {{ number_format($pago->monto, 2) }}</td></tr>
    </table>
    <hr>
    <div class="foot">Gracias por su preferencia</div>
</body>
</html>
