<?php

namespace App\Filament\Resources\Vehiculos;

use App\Filament\Resources\Vehiculos\Pages\CreateVehiculo;
use App\Filament\Resources\Vehiculos\Pages\EditVehiculo;
use App\Filament\Resources\Vehiculos\Pages\ListVehiculos;
use App\Filament\Resources\Vehiculos\Pages\ViewVehiculo;
use App\Filament\Resources\Vehiculos\Schemas\VehiculoForm;
use App\Filament\Resources\Vehiculos\Schemas\VehiculoInfolist;
use App\Filament\Resources\Vehiculos\Tables\VehiculosTable;
use App\Models\Vehiculo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Operación';

    protected static ?string $modelLabel = 'vehículo';

    protected static ?string $pluralModelLabel = 'vehículos';

    public static function form(Schema $schema): Schema
    {
        return VehiculoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehiculosTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VehiculoInfolist::configure($schema);
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
            'index' => ListVehiculos::route('/'),
            'create' => CreateVehiculo::route('/create'),
            'view' => ViewVehiculo::route('/{record}'),
            'edit' => EditVehiculo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
