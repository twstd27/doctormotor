<?php

namespace App\Filament\Resources\CajaCierres;

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

class CajaCierreResource extends Resource
{
    protected static ?string $model = CajaCierre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?string $navigationLabel = 'Caja';

    protected static ?string $modelLabel = 'caja';

    protected static ?string $pluralModelLabel = 'cajas';

    public static function form(Schema $schema): Schema
    {
        return CajaCierreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CajaCierresTable::configure($table);
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
