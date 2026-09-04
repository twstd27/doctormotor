<?php

namespace App\Filament\Resources\OrdenesTrabajo;

use App\Filament\Resources\OrdenesTrabajo\Pages\CreateOrdenTrabajo;
use App\Filament\Resources\OrdenesTrabajo\Pages\EditOrdenTrabajo;
use App\Filament\Resources\OrdenesTrabajo\Pages\ListOrdenesTrabajo;
use App\Filament\Resources\OrdenesTrabajo\Schemas\OrdenTrabajoForm;
use App\Filament\Resources\OrdenesTrabajo\Tables\OrdenesTrabajoTable;
use App\Models\OrdenTrabajo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrdenTrabajoResource extends Resource
{
    protected static ?string $model = OrdenTrabajo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Órdenes de trabajo';

    // Sin esto, Filament arma la URL como /admin/ordenes-trabajo/orden-trabajos (mezcla el
    // namespace de la carpeta con su propio plural adivinado) — mismo patrón de bug de
    // pluralización que ya se dio con Proveedores, ver ProveedorResource.
    protected static ?string $slug = 'ordenes-trabajo';

    protected static ?string $modelLabel = 'orden de trabajo';

    protected static ?string $pluralModelLabel = 'órdenes de trabajo';

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
            //
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
