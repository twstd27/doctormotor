<?php

use App\Filament\Pages\InformeIngresosEgresos;
use App\Models\Pago;
use App\Models\RepartoUtilidad;
use Barryvdh\DomPDF\Facade\Pdf;
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
});
