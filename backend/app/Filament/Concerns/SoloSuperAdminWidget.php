<?php

namespace App\Filament\Concerns;

/**
 * Igual que SoloSuperAdmin pero para Widgets del Dashboard (cuyo método se llama canView).
 */
trait SoloSuperAdminWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->rol === 'super_admin';
    }
}
