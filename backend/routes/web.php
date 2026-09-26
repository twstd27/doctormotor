<?php

use App\Filament\Pages\InformeIngresosEgresos;
use App\Models\Pago;
use App\Models\RepartoUtilidad;
use App\Models\Vehiculo;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Descargas de PDF disparadas desde el panel Filament — usan el guard 'web' (la sesión
// del propio panel), no Sanctum, así el botón funciona con un simple link/nueva pestaña
// en vez de tener que mandar el Bearer token a mano.
Route::middleware('auth')->prefix('admin-pdf')->group(function () {
    Route::get('/pagos/{pago}/recibo', function (Pago $pago) {
        $pago->load('cliente', 'ordenTrabajo');

        return Pdf::loadView('pdf.recibo', ['pago' => $pago])->stream("recibo-{$pago->id}.pdf");
    })->name('admin-pdf.pagos.recibo');

    Route::get('/pagos/{pago}/ticket', function (Pago $pago) {
        $pago->load('cliente', 'ordenTrabajo');

        return Pdf::loadView('pdf.recibo-ticket', ['pago' => $pago])
            ->setPaper([0, 0, 226.77, 700], 'portrait')
            ->stream("ticket-{$pago->id}.pdf");
    })->name('admin-pdf.pagos.ticket');

    Route::get('/informe-ingresos-egresos/csv', function (\Illuminate\Http\Request $request) {
        $desde = $request->query('desde', now()->subMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $filas = InformeIngresosEgresos::desglosePara($desde, $hasta);

        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Fecha', 'Ingresos', 'Egresos', 'Resultado']);
            foreach ($filas as $fila) {
                fputcsv($out, [$fila['fecha'], $fila['ingresos'], $fila['egresos'], $fila['resultado']]);
            }
            fclose($out);
        }, "ingresos-egresos_{$desde}_{$hasta}.csv");
    })->name('informe-ingresos-egresos.csv');

    Route::get('/reparto-utilidades/{reparto_utilidad}/comprobante', function (RepartoUtilidad $reparto_utilidad) {
        $reparto_utilidad->load('detalle.socio', 'generadoPor');

        return Pdf::loadView('pdf.reparto-utilidad', ['reparto' => $reparto_utilidad])
            ->stream("reparto-{$reparto_utilidad->id}.pdf");
    })->name('admin-pdf.reparto-utilidades.comprobante');

    Route::get('/vehiculos/{vehiculo}/qr', function (Vehiculo $vehiculo) {
        $url = rtrim(config('services.frontend.url'), '/')."/qr/{$vehiculo->qr_token}";
        // SvgWriter en vez del PngWriter por defecto: este último usa GD, y el droplet de
        // producción (compartido con otras apps) no lo tiene habilitado para PHP 8.3, lo que
        // tira 500 "Unable to generate image: please check if the GD extension is enabled".
        // El SVG no depende de GD, es más nítido para imprimir, y DomPDF lo renderiza directo
        // como vectores (confirmado localmente: 879 operadores de dibujo en el content stream).
        $qrDataUri = (new Builder())->build(writer: new SvgWriter(), data: $url, size: 320, margin: 8)->getDataUri();

        // DomPDF necesita un directorio temporal escribible para volcar ahí el archivo del QR
        // antes de parsearlo. El default del paquete es sys_get_temp_dir(), pero bajo el SAPI
        // "cli-server" (el que usa `php artisan serve` en Windows/Herd) esa función resuelve a
        // C:\WINDOWS — no escribible — y el QR se pierde en silencio (dompdf cae al ícono de
        // "imagen rota" sin lanzar error). Se fija explícito a un path propio de Laravel que
        // sabemos que existe y es escribible, sin depender del entorno del SAPI.
        return Pdf::setOption('tempDir', storage_path('framework/cache'))
            ->loadView('pdf.vehiculo-qr', ['vehiculo' => $vehiculo, 'qrDataUri' => $qrDataUri])
            ->setPaper([0, 0, 198.43, 255.12], 'portrait') // 70mm x 90mm
            ->stream("qr-{$vehiculo->placa}.pdf");
    })->name('admin-pdf.vehiculos.qr');
});
