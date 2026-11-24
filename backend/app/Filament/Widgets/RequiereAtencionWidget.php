<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Compras\CompraResource;
use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Filament\Resources\Productos\ProductoResource;
use App\Models\OrdenTrabajo;
use App\Models\Producto;
use App\Services\WhatsAppService;
use App\Support\EstadoOrden;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * "Requiere tu atención": lo que hoy conviene mirar primero (HANDOFF §4, Inicio). Cada
 * tarjeta muestra un caso concreto y lleva directo a la acción.
 */
class RequiereAtencionWidget extends Widget
{
    protected string $view = 'filament.widgets.requiere-atencion';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    private function nombreVehiculo(OrdenTrabajo $orden): string
    {
        return trim("{$orden->vehiculo?->marca} {$orden->vehiculo?->modelo}");
    }

    private function masCasos(Collection $casos): ?string
    {
        $otros = $casos->count() - 1;

        return $otros > 0 ? "y {$otros} más" : null;
    }

    /** @return array<int, array<string, mixed>> */
    private function tarjetas(): array
    {
        $whatsapp = app(WhatsAppService::class);
        $abiertas = OrdenTrabajo::query()->whereNotIn('estado', EstadoOrden::CERRADOS);
        $tarjetas = [];

        // Listas para entregar: avisar al cliente.
        $listas = (clone $abiertas)->where('estado', 'listo_entrega')->with(['vehiculo', 'cliente'])->orderBy('updated_at')->get();
        if ($listas->isNotEmpty()) {
            $orden = $listas->first();
            $tarjetas[] = [
                'color' => 'lima',
                'titulo' => 'Listo para entregar',
                'orden' => $orden,
                'texto' => $this->nombreVehiculo($orden).' — avisa a '.$orden->cliente?->nombre,
                'mas' => $this->masCasos($listas),
                'accion' => 'Avisar por WhatsApp →',
                'url' => filled($orden->cliente?->telefono_whatsapp)
                    ? $whatsapp->linkWaMe($orden->cliente->telefono_whatsapp, 'ot_lista_entrega', [
                        'vehiculo' => $this->nombreVehiculo($orden),
                        'codigo_ot' => $orden->codigo,
                    ])
                    : OrdenTrabajoResource::getUrl('view', ['record' => $orden]),
                'externa' => filled($orden->cliente?->telefono_whatsapp),
            ];
        }

        // Entregas vencidas.
        $vencidas = (clone $abiertas)->whereDate('fecha_entrega_estimada', '<', today())->with(['vehiculo', 'cliente'])->orderBy('fecha_entrega_estimada')->get();
        if ($vencidas->isNotEmpty()) {
            $orden = $vencidas->first();
            $tarjetas[] = [
                'color' => 'rojo',
                'titulo' => 'Entrega vencida',
                'orden' => $orden,
                'texto' => $this->nombreVehiculo($orden).' — prometido el '.$orden->fecha_entrega_estimada->format('d/m').', sigue '.mb_strtolower(EstadoOrden::etiqueta($orden->estado)),
                'mas' => $this->masCasos($vencidas),
                'accion' => 'Ver orden →',
                'url' => OrdenTrabajoResource::getUrl('view', ['record' => $orden]),
                'externa' => false,
            ];
        }

        // Presupuestos sin respuesta.
        $esperando = (clone $abiertas)->where('estado', 'esperando_aprobacion')->with(['vehiculo', 'cliente'])->orderBy('updated_at')->get();
        if ($esperando->isNotEmpty()) {
            $orden = $esperando->first();
            $presupuesto = $orden->presupuestos()->where('estado', 'enviado')->latest()->first();
            $dias = (int) ($presupuesto?->updated_at?->diffInDays(now()) ?? 0);
            $tarjetas[] = [
                'color' => 'ambar',
                'titulo' => 'Esperando aprobación'.($dias > 0 ? ' · '.$dias.($dias === 1 ? ' día' : ' días') : ''),
                'orden' => $orden,
                'texto' => $this->nombreVehiculo($orden).($presupuesto ? ' — presupuesto de Bs '.number_format((float) $presupuesto->total, 2, ',', '.').' sin respuesta' : ' — presupuesto sin respuesta'),
                'mas' => $this->masCasos($esperando),
                'accion' => filled($orden->cliente?->telefono_whatsapp) ? 'Recordar al cliente →' : 'Ver orden →',
                'url' => filled($orden->cliente?->telefono_whatsapp)
                    ? $whatsapp->linkWaMe($orden->cliente->telefono_whatsapp, 'ot_presupuesto_listo', [
                        'vehiculo' => $this->nombreVehiculo($orden),
                        'codigo_ot' => $orden->codigo,
                    ])
                    : OrdenTrabajoResource::getUrl('view', ['record' => $orden]),
                'externa' => filled($orden->cliente?->telefono_whatsapp),
            ];
        }

        // Stock bajo: crear una compra.
        $bajos = Producto::query()->where('activo', true)->whereColumn('stock_actual', '<=', 'stock_minimo')->orderBy('nombre')->get();
        if ($bajos->isNotEmpty()) {
            $tarjetas[] = [
                'color' => 'azul',
                'titulo' => 'Stock bajo',
                'orden' => null,
                'texto' => $bajos->first()->nombre.($bajos->count() > 1 ? ' y '.($bajos->count() - 1).($bajos->count() === 2 ? ' producto más' : ' productos más').' bajo el mínimo' : ' bajo el mínimo'),
                'mas' => null,
                'accion' => 'Crear compra →',
                'url' => CompraResource::canCreate() ? CompraResource::getUrl('create') : ProductoResource::getUrl('index'),
                'externa' => false,
            ];
        }

        return $tarjetas;
    }

    protected function getViewData(): array
    {
        return ['tarjetas' => $this->tarjetas()];
    }
}
