<?php

use App\Filament\Pages\InformeIngresosEgresos;
use App\Models\Pago;
use App\Models\RepartoUtilidad;
use App\Models\Vehiculo;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
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
        $qrDataUri = (new Builder())->build(data: $url, size: 320, margin: 8)->getDataUri();

        // DomPDF trata las imágenes data:URI igual que las remotas para el chequeo de
        // isRemoteEnabled (que por defecto está en false) — sin esto, el <img> del QR se
        // descarta en silencio y el PDF sale sin la imagen. Seguro acá porque la data:URI
        // la generamos nosotros mismos, no viene de input del usuario.
        // OJO: setOption() (singular) — setOptions() en plural REEMPLAZA todo el objeto
        // Options en vez de mezclar, y se lleva de encuentro fontDir/fontCache ya
        // configurados, causando "Path cannot be empty" de php-font-lib al buscar la fuente.
        // DomPDF necesita un directorio temporal escribible para volcar ahí la imagen del
        // QR antes de embeberla. El default del paquete es sys_get_temp_dir(), pero bajo el
        // SAPI "cli-server" (el que usa `php artisan serve` en Windows/Herd) esa función
        // resuelve a C:\WINDOWS — no escribible — y el QR se pierde en silencio (dompdf cae
        // al ícono de "imagen rota" sin lanzar error). Se fija explícito a un path propio de
        // Laravel que sabemos que existe y es escribible, sin depender del entorno del SAPI.
        return Pdf::setOption('tempDir', storage_path('framework/cache'))
            ->loadView('pdf.vehiculo-qr', ['vehiculo' => $vehiculo, 'qrDataUri' => $qrDataUri])
            ->setPaper([0, 0, 198.43, 255.12], 'portrait') // 70mm x 90mm
            ->stream("qr-{$vehiculo->placa}.pdf");
    })->name('admin-pdf.vehiculos.qr');
});
