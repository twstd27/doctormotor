<?php

namespace App\Filament\Resources\MovimientosInventario;

use App\Filament\Resources\MovimientosInventario\Pages\ListMovimientosInventario;
use App\Filament\Resources\MovimientosInventario\Tables\MovimientosInventarioTable;
use App\Models\MovimientoInventario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MovimientoInventarioResource extends Resource
{
    protected static ?string $model = MovimientoInventario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Kardex';

    // Registro de auditoría — se genera solo desde Compras, Costos directos o ajustes de
    // stock (ProductoController::ajuste vía API); acá es de solo lectura.
    protected static ?string $slug = 'kardex';

    protected static ?string $modelLabel = 'movimiento de inventario';

    protected static ?string $pluralModelLabel = 'movimientos de inventario';

    public static function table(Table $table): Table
    {
        return MovimientosInventarioTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMovimientosInventario::route('/'),
        ];
    }
}
