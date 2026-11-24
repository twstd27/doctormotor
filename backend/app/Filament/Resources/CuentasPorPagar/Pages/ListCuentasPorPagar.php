<?php

namespace App\Filament\Resources\CuentasPorPagar\Pages;

use App\Filament\Resources\CuentasPorPagar\CuentaPorPagarResource;
use App\Models\CuentaPorPagar;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListCuentasPorPagar extends ListRecords
{
    protected static string $resource = CuentaPorPagarResource::class;

    public function getSubheading(): string
    {
        return 'Lo que se le debe a los proveedores por las compras registradas.';
    }

    public function content(Schema $schema): Schema
    {
        $abiertas = CuentaPorPagar::where('saldo_pendiente', '>', 0);

        return $schema->components([
            View::make('filament.cuentas.resumen')->viewData([
                'porPagar' => (float) (clone $abiertas)->sum('saldo_pendiente'),
                'cuentas' => (clone $abiertas)->count(),
                'proveedores' => (clone $abiertas)->distinct('proveedor_id')->count('proveedor_id'),
                'pagado' => (float) CuentaPorPagar::query()->selectRaw('COALESCE(SUM(monto_original - saldo_pendiente), 0) as total')->value('total'),
                'vencidas' => (clone $abiertas)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', today())->count(),
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
            'pendientes' => Tab::make('Por pagar')
                ->badge(CuentaPorPagar::where('saldo_pendiente', '>', 0)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('saldo_pendiente', '>', 0)),
            'pagadas' => Tab::make('Pagadas')
                ->badge(CuentaPorPagar::where('saldo_pendiente', '<=', 0)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('saldo_pendiente', '<=', 0)),
            'todas' => Tab::make('Todas'),
        ];
    }
}
