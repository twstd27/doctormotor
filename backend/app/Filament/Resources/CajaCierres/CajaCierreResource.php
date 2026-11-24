<?php

namespace App\Filament\Resources\CajaCierres;

use App\Filament\Concerns\TituloEnOracion;
use App\Filament\Resources\CajaCierres\Pages\CreateCajaCierre;
use App\Filament\Resources\CajaCierres\Pages\ListCajaCierres;
use App\Filament\Resources\CajaCierres\Schemas\CajaCierreForm;
use App\Filament\Resources\CajaCierres\Tables\CajaCierresTable;
use App\Models\CajaCierre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CajaCierreResource extends Resource
{
    use TituloEnOracion;

    protected static ?string $model = CajaCierre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Caja';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Caja del día';

    protected static ?string $modelLabel = 'caja';

    protected static ?string $pluralModelLabel = 'cajas';

    // La Caja del día es la pantalla del menú; esta lista queda como historial de cierres
    // (se llega con "Ver todos") y para abrir la caja.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CajaCierreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CajaCierresTable::configure($table);
    }

    // Cada cajero ve solo sus propios turnos; el arqueo de los demás lo ve el super_admin.
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user?->rol === 'super_admin' ? $query : $query->where('cajero_id', $user?->id);
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
            'index' => ListCajaCierres::route('/'),
            'create' => CreateCajaCierre::route('/create'),
        ];
    }
}
