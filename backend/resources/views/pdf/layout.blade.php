<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #171B21; font-size: 12px; }
        .header { display: table; width: 100%; margin-bottom: 18px; border-bottom: 2px solid #171B21; padding-bottom: 12px; }
        .header .brand { display: table-cell; }
        .header .brand .name { font-size: 18px; font-weight: bold; }
        .header .brand .sub { font-size: 10px; color: #565F6D; text-transform: uppercase; letter-spacing: 1px; }
        .header .meta { display: table-cell; text-align: right; vertical-align: top; font-size: 10px; color: #565F6D; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .muted { color: #565F6D; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
        table.data th { text-align: left; background: #F1DDCA; color: #33495E; padding: 6px 8px; font-size: 10px; text-transform: uppercase; }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #D7DBE1; }
        table.data td.num, table.data th.num { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #171B21; }
        .footer { margin-top: 24px; font-size: 9px; color: #8A939C; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <div class="name">DOCTOR MOTOR</div>
            <div class="sub">Mustang's Garage</div>
        </div>
        <div class="meta">
            @yield('meta')
        </div>
    </div>

    @yield('content')

    <div class="footer">Documento generado automáticamente por el sistema de gestión de Doctor Motor.</div>
</body>
</html>
