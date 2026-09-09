<?php

namespace App\Filament\Resources\Notificaciones;

use App\Filament\Resources\Notificaciones\Pages\ListNotificaciones;
use App\Filament\Resources\Notificaciones\Tables\NotificacionesTable;
use App\Models\Notificacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificacionResource extends Resource
{
    protected static ?string $model = Notificacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Notificaciones';

    // Sin esto, Filament arma la URL como /admin/notificaciones/notificacions (mezcla el
    // namespace de la carpeta con su propio plural adivinado en inglés) — mismo bug de
    // pluralización en español que ya se dio con OrdenTrabajo y Proveedor.
    protected static ?string $slug = 'notificaciones';

    // Registro de auditoría de envíos (WhatsApp por ahora) — de solo lectura, se generan
    // desde WhatsAppService, no hay alta ni edición manual.
    protected static ?string $modelLabel = 'notificación';

    protected static ?string $pluralModelLabel = 'notificaciones';

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
