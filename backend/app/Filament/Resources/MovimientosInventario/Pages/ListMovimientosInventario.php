<?php

namespace App\Filament\Resources\MovimientosInventario\Pages;

use App\Filament\Resources\MovimientosInventario\MovimientoInventarioResource;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListMovimientosInventario extends ListRecords
{
    protected static string $resource = MovimientoInventarioResource::class;

    // La lista de productos enlaza acá con el producto ya elegido (?tableFilters[producto_id]…).
    #[Url]
    public ?array $tableFilters = null;

    public function getSubheading(): string
    {
        return 'Cada entrada y salida de inventario, con el saldo después de cada movimiento.';
    }

    /** Producto que se está mirando, si hay uno elegido en el filtro. */
    private function productoElegido(): ?Producto
    {
        $id = $this->tableFilters['producto_id']['value'] ?? null;

        return filled($id) ? Producto::find($id) : null;
    }

    public function content(Schema $schema): Schema
    {
        $producto = $this->productoElegido();
        $desde = now()->subDays(90);

        $resumen = $producto
            ? [
                'producto' => $producto,
                'entradas' => (float) $producto->movimientos()->where('fecha', '>=', $desde)->where('cantidad', '>', 0)->sum('cantidad'),
                'salidas' => abs((float) $producto->movimientos()->where('fecha', '>=', $desde)->where('cantidad', '<', 0)->sum('cantidad')),
            ]
            : null;

        return $schema->components([
            View::make('filament.kardex.resumen')->viewData(['resumen' => $resumen]),
            $this->getTabsContentComponent(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    public function getTabs(): array
    {
        $cuenta = fn (string $tipo) => MovimientoInventario::where('tipo', $tipo)->count();

        return [
            'todos' => Tab::make('Todos')
                ->badge(MovimientoInventario::count()),
            'entradas' => Tab::make('Entradas por compra')
                ->badge($cuenta('entrada_compra'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo', 'entrada_compra')),
            'salidas' => Tab::make('Salidas por orden')
                ->badge($cuenta('salida_ot'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo', 'salida_ot')),
            'ajustes' => Tab::make('Ajustes')
                ->badge($cuenta('ajuste'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo', 'ajuste')),
        ];
    }
}
