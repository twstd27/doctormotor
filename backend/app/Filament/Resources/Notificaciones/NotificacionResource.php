<?php

namespace App\Filament\Resources\Notificaciones;

use App\Filament\Concerns\TituloEnOracion;
use App\Filament\Resources\Notificaciones\Pages\ListNotificaciones;
use App\Filament\Resources\Notificaciones\Tables\NotificacionesTable;
use App\Models\Notificacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificacionResource extends Resource
{
    use TituloEnOracion;

    protected static ?string $model = Notificacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Mensajes';

    // Sin esto, Filament arma la URL como /admin/notificaciones/notificacions (mezcla el
    // namespace de la carpeta con su propio plural adivinado en inglés) — mismo bug de
    // pluralización en español que ya se dio con OrdenTrabajo y Proveedor.
    protected static ?string $slug = 'notificaciones';

    // Registro de auditoría de envíos (WhatsApp por ahora) — de solo lectura, se generan
    // desde WhatsAppService, no hay alta ni edición manual.
    // Contador rojo del menú: mensajes que WhatsApp no pudo entregar y esperan atención.
    public static function getNavigationBadge(): ?string
    {
        $cantidad = Notificacion::whereIn('estado', ['por_enviar', 'fallido'])->count();

        return $cantidad > 0 ? (string) $cantidad : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return Notificacion::where('estado', 'fallido')->exists() ? 'danger' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Mensajes por enviar o fallidos';
    }

    protected static ?string $modelLabel = 'mensaje';

    protected static ?string $pluralModelLabel = 'mensajes';

    public static function table(Table $table): Table
    {
        return NotificacionesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificaciones::route('/'),
        ];
    }
}
