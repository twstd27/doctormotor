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
use App\Http\Controllers\Api\ReglaRepartoController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\RepartoUtilidadController;
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

    Route::middleware('auth:sanctum')->group(function () {

        // Sección 1 — Clientes, Vehículos y Cuentas
        Route::apiResource('clientes', ClienteController::class);
        Route::post('/clientes/{cliente}/invitar', [ClienteController::class, 'invitar']);
        Route::get('/clientes/{cliente}/vehiculos', [ClienteController::class, 'vehiculos']);
        Route::post('/clientes/{cliente}/vehiculos', [ClienteController::class, 'storeVehiculo']);
        Route::get('/clientes/{cliente}/cuenta', [PagoController::class, 'cuenta']);

        Route::get('/vehiculos/{vehiculo}', [VehiculoController::class, 'show']);
        Route::put('/vehiculos/{vehiculo}', [VehiculoController::class, 'update']);
        Route::delete('/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy']);
        Route::get('/vehiculos/{vehiculo}/historial', [VehiculoController::class, 'historial']);
        Route::get('/vehiculos/{vehiculo}/historial/pdf', [VehiculoController::class, 'historialPdf']);

        Route::get('/me/vehiculos', [VehiculoController::class, 'mios']);
        Route::get('/me/ordenes-trabajo', [OrdenTrabajoController::class, 'misOrdenesCliente']);

        // Sección 2 — Órdenes de Trabajo y Kanban
        Route::get('/ordenes-trabajo/mias', [OrdenTrabajoController::class, 'mias']);
        Route::apiResource('ordenes-trabajo', OrdenTrabajoController::class)->except(['destroy']);
        Route::patch('/ordenes-trabajo/{ordenes_trabajo}/estado', [OrdenTrabajoController::class, 'cambiarEstado']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/asignar-tecnico', [OrdenTrabajoController::class, 'asignarTecnico']);
        Route::get('/ordenes-trabajo/{ordenes_trabajo}/historial-estados', [OrdenTrabajoController::class, 'historialEstados']);

        Route::post('/ordenes-trabajo/{ordenes_trabajo}/inspeccion', [InspeccionController::class, 'store']);
        Route::put('/ordenes-trabajo/{ordenes_trabajo}/inspeccion', [InspeccionController::class, 'update']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/inspeccion/firma', [InspeccionController::class, 'firma']);

        Route::get('/ordenes-trabajo/{ordenes_trabajo}/evidencias', [EvidenciaController::class, 'index']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/evidencias', [EvidenciaController::class, 'store']);
        Route::post('/evidencias/sync-batch', [EvidenciaController::class, 'syncBatch']);
        Route::delete('/evidencias/{evidencia}', [EvidenciaController::class, 'destroy']);

        // Sección 3 — Presupuestos y Aprobaciones
        Route::get('/ordenes-trabajo/{ordenes_trabajo}/presupuestos', [PresupuestoController::class, 'index']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/presupuestos', [PresupuestoController::class, 'store']);
        Route::get('/presupuestos/{presupuesto}', [PresupuestoController::class, 'show']);
        Route::get('/presupuestos/{presupuesto}/pdf', [PresupuestoController::class, 'pdf']);
        Route::put('/presupuestos/{presupuesto}', [PresupuestoController::class, 'update']);
        Route::post('/presupuestos/{presupuesto}/enviar', [PresupuestoController::class, 'enviar']);
        Route::post('/presupuestos/{presupuesto}/items/{item}/responder', [PresupuestoController::class, 'responderItem']);
        Route::post('/presupuestos/{presupuesto}/responder', [PresupuestoController::class, 'responder']);
        Route::post('/ordenes-trabajo/{ordenes_trabajo}/adicionales', [PresupuestoController::class, 'adicionales']);

        // Sección 4 — Finanzas
        Route::post('/pagos', [PagoController::class, 'store']);
        Route::get('/pagos/{pago}/recibo', [PagoController::class, 'recibo']);
        Route::get('/pagos/{pago}/recibo/ticket', [PagoController::class, 'reciboTicket']);
        Route::get('/caja/actual', [CajaController::class, 'actual']);
        Route::post('/caja/apertura', [CajaController::class, 'apertura']);
        Route::post('/caja/{caja}/cierre', [CajaController::class, 'cierre']);
        Route::get('/caja/cierres', [CajaController::class, 'cierres']);

        Route::post('/ordenes-trabajo/{ordenes_trabajo}/costos-directos', [CostoDirectoController::class, 'store']);
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

        // Sección 5 — Inventario y Proveedores
        Route::get('/productos/alertas-stock', [ProductoController::class, 'alertasStock']);
        Route::apiResource('productos', ProductoController::class)->except(['destroy']);
        Route::get('/productos/{producto}/movimientos', [ProductoController::class, 'movimientos']);
        Route::post('/productos/{producto}/ajuste', [ProductoController::class, 'ajuste']);

        Route::apiResource('proveedores', ProveedorController::class)
            ->except(['destroy'])
            ->parameters(['proveedores' => 'proveedor']);
        Route::apiResource('compras', CompraController::class)->only(['index', 'store', 'show']);

        Route::get('/cuentas-por-pagar', [CuentaPorPagarController::class, 'index']);
        Route::post('/cuentas-por-pagar/{cuentas_por_pagar}/pagos', [CuentaPorPagarController::class, 'pagos']);

        // Sección 7 — Notificaciones (auditoría/reintento, requiere sesión)
        Route::get('/notificaciones', [NotificacionController::class, 'index']);
        Route::post('/notificaciones/{notificacion}/reintentar', [NotificacionController::class, 'reintentar']);
        Route::get('/me/notificaciones', [NotificacionController::class, 'misNotificaciones']);
        Route::get('/me/perfil', [PerfilController::class, 'show']);
        Route::put('/me/perfil', [PerfilController::class, 'update']);
    });

});
