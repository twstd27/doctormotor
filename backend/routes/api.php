<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\CostoDirectoController;
use App\Http\Controllers\Api\CuentaPorPagarController;
use App\Http\Controllers\Api\EvidenciaController;
use App\Http\Controllers\Api\GastoEgresoController;
use App\Http\Controllers\Api\InspeccionController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\OrdenTrabajoController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\PresupuestoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ServicioFrecuenteController;
use App\Http\Controllers\Api\ReglaRepartoController;
use App\Http\Controllers\Api\RepartoUtilidadController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\SocioController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Sección 7 — webhook público, validado por Meta (verify token / firma), no por Sanctum.
Route::prefix('v1/webhooks')->group(function () {
    Route::get('/whatsapp', [WebhookController::class, 'verificarWhatsapp']);
    Route::post('/whatsapp', [WebhookController::class, 'whatsapp']);
});

Route::prefix('v1')->group(function () {

    // Historial público por QR — sin auth, lo abre cualquiera que escanee el sticker del auto.
    Route::get('/vehiculos/qr/{token}', [VehiculoController::class, 'historialPublico']);

    // Sección 0 — Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/login/whatsapp-link', [AuthController::class, 'loginWhatsappLink']);
        Route::post('/whatsapp/verify/{token}', [AuthController::class, 'verifyWhatsapp']);
        Route::get('/google/redirect', [AuthController::class, 'googleRedirect']);
        Route::post('/google/callback', [AuthController::class, 'googleCallback']);
        Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
        Route::post('/password/reset', [AuthController::class, 'resetPassword']);
        Route::get('/invitacion/{token}', [AuthController::class, 'invitacionTecnico']);
        Route::post('/invitacion/{token}/aceptar', [AuthController::class, 'aceptarInvitacion']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // Autorización: `rol:a,b` limita por rol (el super_admin siempre pasa) y `can:...` limita por
    // pertenencia al registro puntual (ver AppServiceProvider). Tabla completa en
    // docs/03-API-ENDPOINTS.md. Resumen de roles:
    //   cajero            = super_admin + cajero (staff del mostrador)
    //   operador_tecnico  = técnico: solo las órdenes que tiene asignadas
    //   cliente           = cliente: solo lo suyo
    Route::middleware('auth:sanctum')->group(function () {

        // Cualquier usuario autenticado — datos de su propia cuenta
        Route::get('/me/vehiculos', [VehiculoController::class, 'mios']);
        Route::get('/me/ordenes-trabajo', [OrdenTrabajoController::class, 'misOrdenesCliente']);
        Route::get('/me/notificaciones', [NotificacionController::class, 'misNotificaciones']);
        Route::get('/me/perfil', [PerfilController::class, 'show']);
        Route::put('/me/perfil', [PerfilController::class, 'update']);

        // Sección 1 — Clientes, Vehículos y Cuentas
        Route::apiResource('clientes', ClienteController::class)->only(['index', 'store', 'show', 'update'])
            ->middleware('rol:cajero');
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->middleware('rol:super_admin');
        Route::post('/clientes/{cliente}/invitar', [ClienteController::class, 'invitar'])->middleware('rol:cajero');
        Route::get('/clientes/{cliente}/vehiculos', [ClienteController::class, 'vehiculos'])
            ->middleware(['rol:cajero,cliente', 'can:ver-cliente,cliente']);
        Route::post('/clientes/{cliente}/vehiculos', [ClienteController::class, 'storeVehiculo'])->middleware('rol:cajero');
        Route::get('/clientes/{cliente}/cuenta', [PagoController::class, 'cuenta'])
            ->middleware(['rol:cajero,cliente', 'can:ver-cliente,cliente']);

        Route::get('/vehiculos/{vehiculo}', [VehiculoController::class, 'show'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-vehiculo,vehiculo']);
        Route::put('/vehiculos/{vehiculo}', [VehiculoController::class, 'update'])->middleware('rol:cajero');
        Route::delete('/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy'])->middleware('rol:super_admin');
        Route::get('/vehiculos/{vehiculo}/historial', [VehiculoController::class, 'historial'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-vehiculo,vehiculo']);
        Route::get('/vehiculos/{vehiculo}/historial/pdf', [VehiculoController::class, 'historialPdf'])
            ->middleware(['rol:cajero,cliente', 'can:ver-vehiculo,vehiculo']);

        // Sección 2 — Órdenes de Trabajo y Kanban
        Route::get('/ordenes-trabajo/mias', [OrdenTrabajoController::class, 'mias'])->middleware('rol:operador_tecnico');
        Route::apiResource('ordenes-trabajo', OrdenTrabajoController::class)->only(['index'])
            ->middleware('rol:cajero,operador_tecnico');
        Route::apiResource('ordenes-trabajo', OrdenTrabajoController::class)->only(['store', 'update'])
            ->middleware('rol:cajero');
        Route::apiResource('ordenes-trabajo', OrdenTrabajoController::class)->only(['show'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-orden-trabajo,ordenes_trabajo']);
        Route::patch('/ordenes-trabajo/{ordenes_trabajo}/estado', [OrdenTrabajoController::class, 'cambiarEstado'])
            ->middleware(['rol:cajero,operador_tecnico', 'can:gestionar-orden-trabajo,ordenes_trabajo']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/asignar-tecnico', [OrdenTrabajoController::class, 'asignarTecnico'])
            ->middleware('rol:cajero');
        Route::get('/ordenes-trabajo/{ordenes_trabajo}/historial-estados', [OrdenTrabajoController::class, 'historialEstados'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-orden-trabajo,ordenes_trabajo']);

        Route::middleware(['rol:cajero,operador_tecnico', 'can:gestionar-orden-trabajo,ordenes_trabajo'])->group(function () {
            Route::post('/ordenes-trabajo/{ordenes_trabajo}/inspeccion', [InspeccionController::class, 'store']);
            Route::put('/ordenes-trabajo/{ordenes_trabajo}/inspeccion', [InspeccionController::class, 'update']);
            Route::post('/ordenes-trabajo/{ordenes_trabajo}/inspeccion/firma', [InspeccionController::class, 'firma']);
            Route::post('/ordenes-trabajo/{ordenes_trabajo}/evidencias', [EvidenciaController::class, 'store']);
        });
        Route::get('/ordenes-trabajo/{ordenes_trabajo}/evidencias', [EvidenciaController::class, 'index'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-orden-trabajo,ordenes_trabajo']);
        Route::post('/evidencias/sync-batch', [EvidenciaController::class, 'syncBatch'])->middleware('rol:operador_tecnico');
        Route::delete('/evidencias/{evidencia}', [EvidenciaController::class, 'destroy'])->middleware('rol:super_admin');

        // Sección 3 — Presupuestos y Aprobaciones
        Route::get('/ordenes-trabajo/{ordenes_trabajo}/presupuestos', [PresupuestoController::class, 'index'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-orden-trabajo,ordenes_trabajo']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/presupuestos', [PresupuestoController::class, 'store'])
            ->middleware(['rol:cajero,operador_tecnico', 'can:gestionar-orden-trabajo,ordenes_trabajo']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/adicionales', [PresupuestoController::class, 'adicionales'])
            ->middleware(['rol:cajero,operador_tecnico', 'can:gestionar-orden-trabajo,ordenes_trabajo']);
        Route::get('/presupuestos/{presupuesto}', [PresupuestoController::class, 'show'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-presupuesto,presupuesto']);
        Route::get('/presupuestos/{presupuesto}/pdf', [PresupuestoController::class, 'pdf'])
            ->middleware(['rol:cajero,operador_tecnico,cliente', 'can:ver-presupuesto,presupuesto']);
        Route::put('/presupuestos/{presupuesto}', [PresupuestoController::class, 'update'])
            ->middleware(['rol:cajero,operador_tecnico', 'can:gestionar-presupuesto,presupuesto']);
        Route::post('/presupuestos/{presupuesto}/enviar', [PresupuestoController::class, 'enviar'])
            ->middleware(['rol:cajero,operador_tecnico', 'can:gestionar-presupuesto,presupuesto']);
        Route::post('/presupuestos/{presupuesto}/items/{item}/responder', [PresupuestoController::class, 'responderItem'])
            ->middleware(['rol:cliente', 'can:responder-presupuesto,presupuesto']);
        Route::post('/presupuestos/{presupuesto}/responder', [PresupuestoController::class, 'responder'])
            ->middleware(['rol:cliente', 'can:responder-presupuesto,presupuesto']);

        // Sección 4 — Finanzas
        Route::post('/pagos', [PagoController::class, 'store'])->middleware('rol:cajero');
        Route::get('/pagos/{pago}/recibo', [PagoController::class, 'recibo'])
            ->middleware(['rol:cajero,cliente', 'can:ver-pago,pago']);
        Route::get('/pagos/{pago}/recibo/ticket', [PagoController::class, 'reciboTicket'])
            ->middleware(['rol:cajero,cliente', 'can:ver-pago,pago']);
        Route::get('/caja/actual', [CajaController::class, 'actual'])->middleware('rol:cajero');
        Route::post('/caja/apertura', [CajaController::class, 'apertura'])->middleware('rol:cajero');
        Route::post('/caja/{caja}/cierre', [CajaController::class, 'cierre'])->middleware('rol:cajero');
        Route::get('/caja/cierres', [CajaController::class, 'cierres'])->middleware('rol:super_admin');

        Route::post('/ordenes-trabajo/{ordenes_trabajo}/costos-directos', [CostoDirectoController::class, 'store'])
            ->middleware(['rol:cajero,operador_tecnico', 'can:gestionar-orden-trabajo,ordenes_trabajo']);

        // Solo super_admin: márgenes, gastos, reportes y reparto de utilidades
        Route::middleware('rol:super_admin')->group(function () {
            Route::get('/ordenes-trabajo/{ordenes_trabajo}/margen', [CostoDirectoController::class, 'margen']);

            Route::apiResource('gastos-egresos', GastoEgresoController::class)
                ->except(['show'])
                ->parameters(['gastos-egresos' => 'gasto_egreso']);

            Route::get('/reportes/dashboard', [ReporteController::class, 'dashboard']);
            Route::get('/reportes/ingresos-egresos', [ReporteController::class, 'ingresosEgresos']);
            Route::get('/reportes/rentabilidad-por-ot', [ReporteController::class, 'rentabilidadPorOt']);

            Route::get('/socios', [SocioController::class, 'index']);
            Route::post('/socios', [SocioController::class, 'store']);
            Route::get('/reglas-reparto', [ReglaRepartoController::class, 'index']);
            Route::put('/reglas-reparto', [ReglaRepartoController::class, 'update']);
            Route::post('/reparto-utilidades/generar', [RepartoUtilidadController::class, 'generar']);
            Route::get('/reparto-utilidades', [RepartoUtilidadController::class, 'index']);
            Route::get('/reparto-utilidades/{reparto_utilidad}', [RepartoUtilidadController::class, 'show']);

            Route::get('/cuentas-por-pagar', [CuentaPorPagarController::class, 'index']);
            Route::post('/cuentas-por-pagar/{cuentas_por_pagar}/pagos', [CuentaPorPagarController::class, 'pagos']);

            // Sección 7 — Notificaciones (auditoría/reintento)
            Route::get('/notificaciones', [NotificacionController::class, 'index']);
            Route::post('/notificaciones/{notificacion}/reintentar', [NotificacionController::class, 'reintentar']);
        });

        // Sección 5 — Inventario y Proveedores
        Route::get('/productos/alertas-stock', [ProductoController::class, 'alertasStock'])->middleware('rol:cajero');
        Route::apiResource('productos', ProductoController::class)->only(['index', 'store', 'show'])
            ->middleware('rol:cajero,operador_tecnico');
        // Atajos de «Agregar ítem» (lavado, diagnóstico…): los usan el técnico y el cajero.
        Route::get('/servicios-frecuentes', [ServicioFrecuenteController::class, 'index'])
            ->middleware('rol:cajero,operador_tecnico');
        Route::put('/productos/{producto}', [ProductoController::class, 'update'])->middleware('rol:super_admin');
        Route::get('/productos/{producto}/movimientos', [ProductoController::class, 'movimientos'])->middleware('rol:super_admin');
        Route::post('/productos/{producto}/ajuste', [ProductoController::class, 'ajuste'])->middleware('rol:super_admin');

        Route::apiResource('proveedores', ProveedorController::class)
            ->except(['destroy'])
            ->parameters(['proveedores' => 'proveedor'])
            ->middleware('rol:cajero');
        Route::apiResource('compras', CompraController::class)->only(['index', 'store', 'show'])
            ->middleware('rol:cajero');
    });

});
