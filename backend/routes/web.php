<?php

use App\Models\Pago;
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
});
