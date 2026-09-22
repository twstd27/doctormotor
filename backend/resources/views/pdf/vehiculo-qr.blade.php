<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm; size: 70mm 90mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #171B21; text-align: center; }
        .name { font-size: 13px; font-weight: bold; }
        .sub { font-size: 9px; color: #565F6D; text-transform: uppercase; letter-spacing: 1px; }
        img.qr { width: 46mm; height: 46mm; margin: 6px 0; }
        .placa { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .instruccion { font-size: 9px; color: #565F6D; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="name">DOCTOR MOTOR</div>
    <div class="sub">Mustang's Garage</div>

    <img class="qr" src="{{ $qrDataUri }}" alt="QR">

    <div class="placa">{{ $vehiculo->placa }}</div>
    <div class="instruccion">Escanea para ver el historial de este vehículo</div>
</body>
</html>
