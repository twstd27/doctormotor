<?php

namespace App\Filament\Concerns;

/**
 * Para Resources y Pages del panel que solo debe ver el super_admin (finanzas de fondo, socios,
 * usuarios). Oculta el ítem del menú y responde 403 si alguien entra por URL directa.
 */
trait SoloSuperAdmin
{
    public static function canAccess(): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }
}
