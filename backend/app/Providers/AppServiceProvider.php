<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use App\Models\Presupuesto;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->definirPermisosDePertenencia();
    }

    /**
     * Quién puede tocar CADA registro (no solo qué rol llama el endpoint — eso lo resuelve el
     * middleware `rol`). Se usan con `->middleware('can:ver-orden-trabajo,ordenes_trabajo')`.
     *
     * Son habilidades con nombre propio y NO Policies de Laravel a propósito: Filament aplica
     * automáticamente cualquier Policy que exista para un modelo, y una Policy parcial dejaría
     * al cajero sin acceso a esos recursos en el panel.
     *
     * - Staff (super_admin, cajero): todo.
     * - Técnico: solo lo de las órdenes que tiene asignadas.
     * - Cliente: solo lo suyo.
     */
    private function definirPermisosDePertenencia(): void
    {
        Gate::before(fn (User $user) => $user->rol === 'super_admin' ? true : null);

        $esStaff = fn (User $user) => in_array($user->rol, ['super_admin', 'cajero'], true);
        $esDelCliente = fn (User $user, int $clienteId) => $user->rol === 'cliente' && $user->cliente?->id === $clienteId;

        // Ver la orden y todo lo que cuelga de ella (historial, evidencias, presupuestos).
        Gate::define('ver-orden-trabajo', fn (User $user, OrdenTrabajo $ot) => $esStaff($user)
            || ($user->rol === 'operador_tecnico' && $ot->tecnico_asignado_id === $user->id)
            || $esDelCliente($user, $ot->cliente_id));

        // Operar sobre la orden (cambiar estado, inspección, evidencias, costos, presupuestos).
        Gate::define('gestionar-orden-trabajo', fn (User $user, OrdenTrabajo $ot) => $esStaff($user)
            || ($user->rol === 'operador_tecnico' && $ot->tecnico_asignado_id === $user->id));

        Gate::define('ver-presupuesto', fn (User $user, Presupuesto $presupuesto) => Gate::forUser($user)
            ->allows('ver-orden-trabajo', $presupuesto->ordenTrabajo));

        Gate::define('gestionar-presupuesto', fn (User $user, Presupuesto $presupuesto) => Gate::forUser($user)
            ->allows('gestionar-orden-trabajo', $presupuesto->ordenTrabajo));

        // Aprobar o rechazar un presupuesto lo hace el cliente dueño (o el super_admin).
        Gate::define('responder-presupuesto', fn (User $user, Presupuesto $presupuesto) => $esDelCliente($user, $presupuesto->ordenTrabajo->cliente_id));

        Gate::define('ver-vehiculo', fn (User $user, Vehiculo $vehiculo) => $esStaff($user)
            || $esDelCliente($user, $vehiculo->cliente_id)
            || ($user->rol === 'operador_tecnico'
                && OrdenTrabajo::where('vehiculo_id', $vehiculo->id)->where('tecnico_asignado_id', $user->id)->exists()));

        Gate::define('ver-cliente', fn (User $user, Cliente $cliente) => $esStaff($user) || $esDelCliente($user, $cliente->id));

        Gate::define('ver-pago', fn (User $user, Pago $pago) => $esStaff($user) || $esDelCliente($user, $pago->cliente_id));
    }
}
