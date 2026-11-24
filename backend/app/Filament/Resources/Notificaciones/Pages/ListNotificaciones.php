<?php

namespace App\Filament\Resources\Notificaciones\Pages;

use App\Filament\Pages\ConectarWhatsapp;
use App\Filament\Resources\Notificaciones\NotificacionResource;
use App\Models\Notificacion;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListNotificaciones extends ListRecords
{
    protected static string $resource = NotificacionResource::class;

    public function getSubheading(): string
    {
        return app(\App\Services\WhatsAppService::class)->modoManual()
            ? 'El sistema arma cada mensaje y tú lo mandas desde tu WhatsApp: abre el chat o copia el texto.'
            : 'Todo lo que el sistema le mandó a clientes y personal por WhatsApp.';
    }

    public function content(Schema $schema): Schema
    {
        $desde = now()->subDays(30);
        $fallidos = Notificacion::where('estado', 'fallido');

        return $schema->components([
            View::make('filament.notificaciones.resumen')->viewData([
                'modoManual' => app(\App\Services\WhatsAppService::class)->modoManual(),
                'porEnviar' => Notificacion::where('estado', 'por_enviar')->count(),
                'fallidos' => (clone $fallidos)->count(),
                'ultimoError' => (clone $fallidos)->latest('id')->first()?->errorLegible(),
                'enviados30' => Notificacion::where('estado', 'enviado')->where('created_at', '>=', $desde)->count(),
                'total30' => Notificacion::where('created_at', '>=', $desde)->count(),
                'urlReconectar' => ConectarWhatsapp::canAccess() ? ConectarWhatsapp::getUrl() : null,
            ]),
            $this->getTabsContentComponent(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'por_enviar';
    }

    public function getTabs(): array
    {
        $grupo = fn (string $clave) => Notificacion::whereIn('plantilla', Notificacion::GRUPOS[$clave])->count();

        return [
            'por_enviar' => Tab::make('Por enviar')
                ->badge(Notificacion::where('estado', 'por_enviar')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'por_enviar')),
            'todos' => Tab::make('Todos')
                ->badge(Notificacion::count()),
            'fallidos' => Tab::make('Fallidos')
                ->badge(Notificacion::where('estado', 'fallido')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'fallido')),
            'presupuestos' => Tab::make('Presupuestos')
                ->badge($grupo('presupuestos'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('plantilla', Notificacion::GRUPOS['presupuestos'])),
            'avances' => Tab::make('Avances de orden')
                ->badge($grupo('avances'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('plantilla', Notificacion::GRUPOS['avances'])),
            'invitaciones' => Tab::make('Invitaciones')
                ->badge($grupo('invitaciones'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('plantilla', Notificacion::GRUPOS['invitaciones'])),
        ];
    }
}
