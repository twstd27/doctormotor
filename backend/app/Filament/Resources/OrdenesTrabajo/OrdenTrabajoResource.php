<?php

namespace App\Filament\Resources\OrdenesTrabajo;

use App\Filament\Concerns\TituloEnOracion;
use App\Filament\Resources\OrdenesTrabajo\Pages\CreateOrdenTrabajo;
use App\Filament\Resources\OrdenesTrabajo\Pages\EditOrdenTrabajo;
use App\Filament\Resources\OrdenesTrabajo\Pages\ListOrdenesTrabajo;
use App\Filament\Resources\OrdenesTrabajo\RelationManagers\CostosDirectosRelationManager;
use App\Filament\Resources\OrdenesTrabajo\Schemas\OrdenTrabajoForm;
use App\Filament\Resources\OrdenesTrabajo\Tables\OrdenesTrabajoTable;
use App\Models\OrdenTrabajo;
use App\Support\EstadoOrden;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoResource extends Resource
{
    use TituloEnOracion;

    protected static ?string $model = OrdenTrabajo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Taller';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Órdenes de trabajo';

    // Sin esto, Filament arma la URL como /admin/ordenes-trabajo/orden-trabajos (mezcla el
    // namespace de la carpeta con su propio plural adivinado) — mismo patrón de bug de
    // pluralización que ya se dio con Proveedores, ver ProveedorResource.
    protected static ?string $slug = 'ordenes-trabajo';

    protected static ?string $modelLabel = 'orden de trabajo';

    protected static ?string $pluralModelLabel = 'órdenes de trabajo';

    protected static ?string $recordTitleAttribute = 'codigo';

    // Búsqueda global (Ctrl+K): por código de orden, placa o nombre del cliente.
    public static function getGloballySearchableAttributes(): array
    {
        return ['codigo', 'vehiculo.placa', 'cliente.nombre'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['vehiculo', 'cliente']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Vehículo' => "{$record->vehiculo?->marca} {$record->vehiculo?->modelo} · {$record->vehiculo?->placa}",
            'Cliente' => $record->cliente?->nombre,
            'Estado' => EstadoOrden::etiqueta($record->estado),
        ];
    }

    // Contador del menú: órdenes que siguen en el taller.
    public static function getNavigationBadge(): ?string
    {
        $activas = OrdenTrabajo::whereNotIn('estado', EstadoOrden::CERRADOS)->count();

        return $activas > 0 ? (string) $activas : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    // "Nueva recepción" es el acceso más usado del día: va como ítem propio del menú.
    public static function getNavigationItems(): array
    {
        $base = static::getRouteBaseName();

        $items = array_map(
            fn (NavigationItem $item) => $item->isActiveWhen(
                fn () => request()->routeIs("{$base}.index", "{$base}.edit"),
            ),
            parent::getNavigationItems(),
        );

        return [
            ...$items,
            NavigationItem::make('Nueva recepción')
                ->group(static::getNavigationGroup())
                ->icon(Heroicon::OutlinedPlusCircle)
                ->sort(1)
                ->url(static::getUrl('create'))
                ->isActiveWhen(fn () => request()->routeIs("{$base}.create"))
                ->visible(fn () => static::canCreate()),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return OrdenTrabajoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdenesTrabajoTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CostosDirectosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdenesTrabajo::route('/'),
            'create' => CreateOrdenTrabajo::route('/create'),
            'edit' => EditOrdenTrabajo::route('/{record}/edit'),
        ];
    }
}
