<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->darkMode(isForced: true)
            ->font('Inter')
            ->colors([
                'primary' => [
                    50 => '244, 252, 228',   100 => '231, 249, 198',
                    200 => '208, 243, 149',  300 => '182, 236, 94',
                    400 => '159, 224, 58',   500 => '143, 214, 46',
                    600 => '116, 179, 31',   700 => '88, 135, 24',
                    800 => '68, 102, 24',    900 => '57, 79, 26',
                    950 => '29, 43, 8',
                ],
                'gray'    => Color::Slate,
                'success' => Color::hex('#8FD62E'),
                'warning' => Color::hex('#E8B23A'),
                'danger'  => Color::hex('#E8654B'),
                'info'    => Color::hex('#4FA9C9'),
            ])
            ->brandLogo(fn () => new HtmlString(
                '<span class="flex items-center gap-2">'
                .'<span class="flex shrink-0 items-center justify-center rounded-md bg-lime-400 text-sm font-bold text-gray-900" style="width:1.875rem;height:1.875rem;">D</span>'
                .'<span class="text-base font-bold text-white">Doctor Motor</span>'
                .'</span>'
            ))
            ->brandLogoHeight('1.875rem')
            ->navigationGroups([
                NavigationGroup::make('Finanzas')->collapsible(false),
                NavigationGroup::make('Operación')->collapsible(false),
                NavigationGroup::make('Inventario')->collapsible(false),
                NavigationGroup::make('Administración')->collapsible(false),
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn () => view('filament.topbar.reloj-fecha'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('filament.topbar.saludo-salir'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
