<?php

namespace App\Filament\Resources\CuentasPorPagar;

use App\Filament\Resources\CuentasPorPagar\Pages\ListCuentasPorPagar;
use App\Filament\Resources\CuentasPorPagar\Tables\CuentasPorPagarTable;
use App\Models\CuentaPorPagar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CuentaPorPagarResource extends Resource
{
    protected static ?string $model = CuentaPorPagar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Cuentas por pagar';

    // Se generan solo desde una Compra (CompraResource) — no hay alta ni edición manual acá,
    // así que no hace falta un slug/parámetro especial ni un form().
    protected static ?string $slug = 'cuentas-por-pagar';

    protected static ?string $modelLabel = 'cuenta por pagar';

    protected static ?string $pluralModelLabel = 'cuentas por pagar';

    public static function table(Table $table): Table
    {
        return CuentasPorPagarTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCuentasPorPagar::route('/'),
        ];
    }
}
