<?php

namespace App\Filament\Resources\Productos;

use App\Filament\Concerns\TituloEnOracion;
use App\Filament\Resources\Productos\Pages\CreateProducto;
use App\Filament\Resources\Productos\Pages\EditProducto;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Filament\Resources\Productos\Schemas\ProductoForm;
use App\Filament\Resources\Productos\Tables\ProductosTable;
use App\Models\Producto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductoResource extends Resource
{
    use TituloEnOracion;

    protected static ?string $model = Producto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'productos';

    // El cajero puede dar de alta productos, pero cambiarlos o borrarlos (precio, nombre,
    // stock mínimo…) es del super_admin: así el inventario no se altera sin rastro.
    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }

    // Contador ámbar del menú: productos activos en o por debajo de su stock mínimo.
    public static function getNavigationBadge(): ?string
    {
        $bajos = Producto::where('activo', true)->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        return $bajos > 0 ? (string) $bajos : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Productos en o por debajo del stock mínimo';
    }

    public static function form(Schema $schema): Schema
    {
        return ProductoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductos::route('/'),
            'create' => CreateProducto::route('/create'),
            'edit' => EditProducto::route('/{record}/edit'),
        ];
    }
}
