<?php

namespace App\Filament\Resources\GastoEgresos;

use App\Filament\Concerns\SoloSuperAdmin;
use App\Filament\Concerns\TituloEnOracion;
use App\Filament\Resources\GastoEgresos\Pages\CreateGastoEgreso;
use App\Filament\Resources\GastoEgresos\Pages\EditGastoEgreso;
use App\Filament\Resources\GastoEgresos\Pages\ListGastoEgresos;
use App\Filament\Resources\GastoEgresos\Schemas\GastoEgresoForm;
use App\Filament\Resources\GastoEgresos\Tables\GastoEgresosTable;
use App\Models\GastoEgreso;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GastoEgresoResource extends Resource
{
    use SoloSuperAdmin;
    use TituloEnOracion;

    protected static ?string $model = GastoEgreso::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Caja';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'gasto';

    protected static ?string $pluralModelLabel = 'gastos y egresos';

    protected static ?string $navigationLabel = 'Gastos y egresos';

    public static function form(Schema $schema): Schema
    {
        return GastoEgresoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GastoEgresosTable::configure($table);
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
            'index' => ListGastoEgresos::route('/'),
            'create' => CreateGastoEgreso::route('/create'),
            'edit' => EditGastoEgreso::route('/{record}/edit'),
        ];
    }
}
