<?php

namespace App\Filament\Resources\Clientes\RelationManagers;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Models\OrdenTrabajo;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Historial de órdenes del cliente, en su ficha. Solo lectura: abre la ficha de cada orden. */
class OrdenesRelationManager extends RelationManager
{
    protected static string $relationship = 'ordenesTrabajo';

    protected static ?string $title = 'Historial de órdenes';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
            ->heading('Historial de órdenes')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('vehiculo')->conSaldo())
            ->columns([
                ViewColumn::make('codigo')
                    ->label('Orden')
                    ->view('filament.ordenes.col-orden'),
                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono'),
                TextColumn::make('descripcion_problema')
                    ->label('Servicio')
                    ->limit(48)
                    ->tooltip(fn (OrdenTrabajo $record) => $record->descripcion_problema),
                ViewColumn::make('estado')
                    ->label('Estado')
                    ->view('filament.ordenes.col-estado'),
                TextColumn::make('total_aprobado')
                    ->label('Total')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->money('BOB', locale: 'es_BO'),
            ])
            ->defaultSort('fecha_ingreso', 'desc')
            ->recordUrl(fn (OrdenTrabajo $record) => OrdenTrabajoResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Todavía no tiene órdenes')
            ->emptyStateDescription('Cuando recepciones un vehículo de este cliente, la orden aparece acá.');
    }
}
