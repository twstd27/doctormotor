<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'categoria', 'rubro', 'concepto', 'monto', 'origen', 'caja_cierre_id', 'registrado_por_id',
    'comprobante_url', 'comprobante_path', 'fecha',
])]
class GastoEgreso extends Model
{
    protected $table = 'gastos_egresos';

    /**
     * Rubros del gasto: nombre, ayuda y si cuenta como costo fijo o variable (la categoría
     * que usan los reportes se deriva de acá, así nadie tiene que elegir las dos cosas).
     */
    public const RUBROS = [
        'alquiler' => ['nombre' => 'Alquiler', 'ayuda' => 'Local del taller', 'categoria' => 'fijo'],
        'servicios' => ['nombre' => 'Servicios', 'ayuda' => 'Luz, agua, internet', 'categoria' => 'fijo'],
        'sueldos' => ['nombre' => 'Sueldos', 'ayuda' => 'Personal y adelantos', 'categoria' => 'fijo'],
        'insumos' => ['nombre' => 'Insumos', 'ayuda' => 'Lijas, químicos, trapos', 'categoria' => 'variable'],
        'herramientas' => ['nombre' => 'Herramientas', 'ayuda' => 'Compra o reparación', 'categoria' => 'variable'],
        'refrigerio' => ['nombre' => 'Refrigerio', 'ayuda' => 'Almuerzo, bebidas del personal', 'categoria' => 'variable'],
        'otros' => ['nombre' => 'Otros', 'ayuda' => 'Lo que no entra arriba', 'categoria' => 'variable'],
    ];

    public const ORIGENES = [
        'caja' => 'Caja (efectivo)',
        'banco' => 'Banco / transferencia',
        'qr' => 'QR',
    ];

    public static function categoriaDeRubro(?string $rubro): string
    {
        return self::RUBROS[$rubro]['categoria'] ?? 'variable';
    }

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function cajaCierre(): BelongsTo
    {
        return $this->belongsTo(CajaCierre::class, 'caja_cierre_id');
    }
}
