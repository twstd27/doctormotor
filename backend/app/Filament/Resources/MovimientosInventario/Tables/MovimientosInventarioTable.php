<?php

namespace App\Filament\Resources\MovimientosInventario\Tables;

use App\Filament\Resources\Compras\CompraResource;
use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MovimientosInventarioTable
{
    public const LABELS_TIPO = [
        'entrada_compra' => 'Entrada por compra',
        'salida_ot' => 'Salida por orden',
        'ajuste' => 'Ajuste manual',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            // Saldo del producto justo después de cada movimiento: el stock de hoy menos todo
            // lo que se movió después. Así coincide siempre con el stock real, incluso si el
            // producto ya tenía unidades antes del primer movimiento registrado.
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['producto:id,nombre,sku', 'user:id,nombre'])
                ->addSelect(['saldo' => MovimientoInventario::query()
                    ->from('movimientos_inventario as m2')
                    ->selectRaw('(SELECT p.stock_actual FROM productos p WHERE p.id = movimientos_inventario.producto_id) - COALESCE(SUM(m2.cantidad), 0)')
                    ->whereColumn('m2.producto_id', 'movimientos_inventario.producto_id')
                    ->where(fn ($q) => $q
                        ->whereColumn('m2.fecha', '>', 'movimientos_inventario.fecha')
                        ->orWhere(fn ($q2) => $q2
                            ->whereColumn('m2.fecha', 'movimientos_inventario.fecha')
                            ->whereColumn('m2.id', '>', 'movimientos_inventario.id'))),
                ]))
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->description(fn (MovimientoInventario $record) => $record->fecha?->format('H:i'))
                    ->fontFamily('mono')
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Movimiento')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'entrada_compra' => 'success',
                        'salida_ot' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => self::LABELS_TIPO[$state] ?? $state),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->description(fn (MovimientoInventario $record) => $record->producto?->sku)
                    ->searchable(['nombre', 'sku'])
                    ->sortable(),
                TextColumn::make('referencia_tipo')
                    ->label('Referencia')
                    ->fontFamily('mono')
                    ->formatStateUsing(fn (MovimientoInventario $record) => match ($record->referencia_tipo) {
                        'compra' => "Compra #{$record->referencia_id}",
                        'orden_trabajo' => "OT #{$record->referencia_id}",
                        default => $record->referencia_tipo ? "{$record->referencia_tipo} #{$record->referencia_id}" : '—',
                    })
                    ->url(fn (MovimientoInventario $record) => match ($record->referencia_tipo) {
                        'compra' => CompraResource::getUrl('view', ['record' => $record->referencia_id]),
                        'orden_trabajo' => OrdenTrabajoResource::getUrl('view', ['record' => $record->referencia_id]),
                        default => null,
                    }),
                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color(fn (MovimientoInventario $record) => $record->cantidad >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '−').rtrim(rtrim(number_format(abs((float) $state), 2, ',', '.'), '0'), ',')),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',')),
                TextColumn::make('user.nombre')
                    ->label('Registrado por')
                    ->placeholder('—'),
            ])
            ->defaultSort('fecha', 'desc')
            ->searchPlaceholder('Producto o SKU')
            ->filters([
                SelectFilter::make('producto_id')
                    ->label('Producto')
                    ->options(fn () => Producto::orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable(),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(self::LABELS_TIPO),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('fecha_desde')->label('Desde'),
                        DatePicker::make('fecha_hasta')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['fecha_desde'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('fecha', '>=', $fecha))
                            ->when($data['fecha_hasta'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('fecha', '<=', $fecha));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['fecha_desde'] ?? null) {
                            $indicators[] = 'Desde '.Carbon::parse($data['fecha_desde'])->format('d/m/Y');
                        }

                        if ($data['fecha_hasta'] ?? null) {
                            $indicators[] = 'Hasta '.Carbon::parse($data['fecha_hasta'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ]);
    }
}
