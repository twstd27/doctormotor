<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'orden_trabajo_id', 'cliente_id', 'cajero_id', 'caja_cierre_id', 'tipo', 'metodo',
    'tipo_documento', 'monto', 'referencia_externa', 'comprobante_url', 'fecha',
    'detalle_tipo', 'detalle_servicio', 'detalle_items',
])]
class Pago extends Model
{
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'datetime',
            'detalle_items' => 'array',
        ];
    }

    /**
     * Líneas para el recibo "detallado": los ítems aprobados de todas las versiones del
     * presupuesto de la OT, tal como quedaron al momento de cobrar (se guarda como snapshot
     * en detalle_items — si el presupuesto cambia después, el recibo ya emitido no cambia).
     *
     * @return array<int, array{descripcion: string, cantidad: string, precio_unitario: string, subtotal: string}>
     */
    public static function itemsAprobadosDe(OrdenTrabajo $ordenTrabajo): array
    {
        return $ordenTrabajo->presupuestos()
            ->with('items')
            ->get()
            ->flatMap(fn (Presupuesto $presupuesto) => $presupuesto->items->where('aprobado', true))
            ->map(fn (PresupuestoItem $item) => [
                'descripcion' => $item->descripcion,
                'cantidad' => $item->cantidad,
                'precio_unitario' => $item->precio_unitario,
                'subtotal' => $item->subtotal,
            ])
            ->values()
            ->all();
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function cajaCierre(): BelongsTo
    {
        return $this->belongsTo(CajaCierre::class, 'caja_cierre_id');
    }
}
