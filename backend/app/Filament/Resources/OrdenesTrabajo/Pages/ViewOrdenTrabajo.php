<?php

namespace App\Filament\Resources\OrdenesTrabajo\Pages;

use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;
use App\Filament\Resources\OrdenesTrabajo\RelationManagers\PagosRelationManager;
use App\Filament\Resources\Pagos\PagoResource;
use App\Services\OrdenTrabajoEstadoService;
use App\Support\EstadoOrden;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Ficha de la orden: reemplaza a "Editar" como pantalla principal de una orden. Muestra el
 * avance, los números y, en pestañas, costos, cobros e historial (HANDOFF §4, Orden-ficha).
 */
class ViewOrdenTrabajo extends ViewRecord
{
    protected static string $resource = OrdenTrabajoResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->codigo;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cobrar')
                ->label('Registrar cobro')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->url(fn () => PagosRelationManager::urlNuevoCobro($this->getRecord()))
                ->visible(fn () => PagoResource::canCreate() && ! $this->getRecord()->estaCerrada()),
            Action::make('avanzar')
                ->label(fn () => self::etiquetaAvance($this->getRecord()->siguienteEstado()))
                ->icon('heroicon-o-arrow-right')
                ->iconPosition('after')
                ->visible(fn () => in_array($this->getRecord()->siguienteEstado(), [null, 'entregado'], true) === false)
                ->action(fn () => $this->avanzarEstado()),
            // Entregar cierra la orden (queda de solo lectura): pide confirmación y avisa si hay saldo.
            Action::make('entregar')
                ->label('Entregar al cliente')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->getRecord()->siguienteEstado() === 'entregado')
                ->requiresConfirmation()
                ->modalHeading('Entregar el vehículo al cliente')
                ->modalDescription(function () {
                    $saldo = $this->getRecord()->saldoPendiente();

                    return $saldo > 0.009
                        ? 'Esta orden todavía tiene un saldo por cobrar de Bs '.number_format($saldo, 2, ',', '.').'. ¿Entregar igual?'
                        : 'La orden se cierra y queda de solo lectura.';
                })
                ->modalSubmitActionLabel('Entregar')
                ->action(fn () => $this->avanzarEstado()),
            ActionGroup::make([
                EditAction::make()->label('Editar datos de la orden'),
            ])->label('Más acciones')->button()->color('gray')->icon('heroicon-o-ellipsis-horizontal')->iconButton(),
        ];
    }

    private function avanzarEstado(): void
    {
        $orden = $this->getRecord();
        $siguiente = $orden->siguienteEstado();

        if (! $siguiente) {
            return;
        }

        app(OrdenTrabajoEstadoService::class)->cambiarA($orden, $siguiente, auth()->id());
        $orden->refresh();

        Notification::make()
            ->title('La orden pasó a '.mb_strtolower(EstadoOrden::etiqueta($siguiente)))
            ->success()
            ->send();
    }

    private static function etiquetaAvance(?string $siguiente): string
    {
        return match ($siguiente) {
            'entregado' => 'Entregar al cliente',
            null => '',
            default => 'Pasar a '.mb_strtolower(EstadoOrden::etiqueta($siguiente)),
        };
    }

    public function content(Schema $schema): Schema
    {
        $orden = $this->getRecord();

        return $schema->components([
            View::make('filament.ordenes.cabecera')->viewData(['orden' => $orden]),
            View::make('filament.ordenes.estadisticas')->viewData(['orden' => $orden]),
            Grid::make(['default' => 1, 'xl' => 3])
                ->schema([
                    Group::make([$this->getRelationManagersContentComponent()])
                        ->columnSpan(['xl' => 2]),
                    Group::make([
                        View::make('filament.ordenes.ingreso')->viewData(['orden' => $orden]),
                        View::make('filament.ordenes.actividad')->viewData(['orden' => $orden]),
                    ])->columnSpan(['xl' => 1]),
                ]),
        ]);
    }
}
