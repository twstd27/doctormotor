<?php

namespace App\Filament\Resources\OrdenesTrabajo\RelationManagers;

use App\Filament\Resources\Pagos\PagoResource;
use App\Models\OrdenTrabajo;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Cobros de la orden, en la pestaña "Cobros" de la ficha. Solo lectura: los cobros se
 * registran desde la pantalla de Cobros (con turno de caja abierto), no desde acá.
 */
class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Cobros';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $cobros = $ownerRecord->pagos()->count();

        return $cobros > 0 ? (string) $cobros : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'anticipo' => 'Anticipo',
                        'parcial' => 'Parcial',
                        'completo' => 'Completo',
                        'abono_deuda' => 'Abono a deuda',
                        default => $state,
                    }),
                TextColumn::make('metodo')
                    ->label('Método')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'efectivo' => 'success',
                        'qr' => 'info',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state) => $state === 'qr' ? 'QR' : ucfirst($state)),
                TextColumn::make('cajero.nombre')
                    ->label('Cajero'),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->money('BOB')
                    ->summarize(Sum::make()->label('Total cobrado')->money('BOB')),
            ])
            ->defaultSort('fecha', 'desc')
            ->headerActions([
                Action::make('registrarCobro')
                    ->label('Registrar cobro')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => self::urlNuevoCobro($this->getOwnerRecord()))
                    ->visible(fn () => PagoResource::canCreate() && ! $this->getOwnerRecord()->estaCerrada()),
            ])
            ->emptyStateHeading('Todavía no hay cobros')
            ->emptyStateDescription('Cuando el cliente pague un anticipo o el total, aparece acá.');
    }

    public static function urlNuevoCobro(OrdenTrabajo $orden): string
    {
        return PagoResource::getUrl('create', [
            'cliente_id' => $orden->cliente_id,
            'orden_trabajo_id' => $orden->id,
        ]);
    }
}
