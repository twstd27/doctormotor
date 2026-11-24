<?php

namespace App\Filament\Resources\Productos\Pages;

use App\Filament\Resources\Compras\CompraResource;
use App\Filament\Resources\Productos\ProductoResource;
use App\Models\Producto;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    #[Url]
    public ?array $tableFilters = null;

    public function getSubheading(): string
    {
        $activos = Producto::where('activo', true)->count();

        return $activos === 1 ? '1 producto activo' : "{$activos} productos activos";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compra')
                ->label('Registrar compra')
                ->icon('heroicon-o-shopping-cart')
                ->color('gray')
                ->url(fn () => CompraResource::getUrl('create'))
                ->visible(fn () => CompraResource::canCreate()),
            CreateAction::make()
                ->label('Nuevo producto')
                ->icon('heroicon-o-plus'),
        ];
    }

    // Aviso de stock arriba de la lista, antes de las pestañas.
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.productos.alerta')->viewData([
                'bajos' => Producto::where('activo', true)->where('stock_actual', '>', 0)->whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
                'sinStock' => Producto::where('activo', true)->where('stock_actual', '<=', 0)->count(),
            ]),
            $this->getTabsContentComponent(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    public function getTabs(): array
    {
        return [
            'activos' => Tab::make('Activos')
                ->badge(Producto::where('activo', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('activo', true)),
            'bajo_minimo' => Tab::make('Bajo mínimo')
                ->badge(Producto::where('activo', true)->where('stock_actual', '>', 0)->whereColumn('stock_actual', '<=', 'stock_minimo')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('activo', true)->where('stock_actual', '>', 0)->whereColumn('stock_actual', '<=', 'stock_minimo')),
            'sin_stock' => Tab::make('Sin stock')
                ->badge(Producto::where('activo', true)->where('stock_actual', '<=', 0)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('activo', true)->where('stock_actual', '<=', 0)),
            'inactivos' => Tab::make('Inactivos')
                ->badge(Producto::where('activo', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('activo', false)),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        // Los accesos directos (?tableFilters[stock_bajo]…) piden stock bajo entre TODOS los
        // productos; en la pestaña "Activos" ya se ve, así que no hace falta cambiarla.
        return 'activos';
    }
}
