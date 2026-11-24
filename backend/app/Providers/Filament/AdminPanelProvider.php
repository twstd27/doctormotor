<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditarPerfil;
use App\Filament\Pages\Auth\Login;
use App\Support\AvatarIniciales;
use Filament\Actions\Action;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->login(Login::class)
            ->profile(EditarPerfil::class, isSimple: false)
            ->darkMode(isForced: true)
            ->defaultAvatarProvider(AvatarIniciales::class)
            // Las tipografías se empaquetan en el tema (resources/css/filament/admin/theme.css)
            // con @fontsource: no se piden a Google/Bunny.
            ->font('Inter Variable', provider: LocalFontProvider::class)
            ->monoFont('JetBrains Mono', provider: LocalFontProvider::class)
            ->colors([
                // Acento lima del sistema (#C5F82A = 500). El texto sobre lima es oscuro: ver theme.css.
                'primary' => [
                    50 => '248, 254, 227', 100 => '239, 253, 190',
                    200 => '226, 252, 141', 300 => '212, 251, 90',
                    400 => '203, 249, 61', 500 => '197, 248, 42',
                    600 => '160, 201, 28', 700 => '122, 155, 24',
                    800 => '92, 117, 24', 900 => '77, 97, 26',
                    950 => '40, 54, 6',
                ],
                // Grafito: 950 = fondo de página, 900 = tarjetas, 800 = botones secundarios, 700 = bordes.
                'gray' => [
                    50 => '242, 244, 239', 100 => '236, 238, 241',
                    200 => '213, 218, 224', 300 => '190, 198, 207',
                    400 => '154, 163, 174', 500 => '139, 149, 161',
                    600 => '96, 106, 118', 700 => '42, 49, 58',
                    800 => '30, 35, 42', 900 => '20, 24, 29',
                    950 => '11, 13, 16',
                ],
                'success' => Color::hex('#C5F82A'),
                'warning' => Color::hex('#FFB547'),
                'danger' => [
                    50 => '255, 241, 238', 100 => '255, 225, 219',
                    200 => '255, 198, 188', 300 => '255, 166, 150',
                    400 => '255, 138, 122', 500 => '229, 96, 77',
                    600 => '200, 70, 52', 700 => '160, 53, 40',
                    800 => '124, 47, 38', 900 => '100, 43, 36',
                    950 => '55, 19, 15',
                ],
                'info' => Color::hex('#6CB4FF'),
                'violet' => Color::hex('#B9A2FF'),
            ])
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->navigationGroups([
                NavigationGroup::make('Taller')->collapsible(false),
                NavigationGroup::make('Caja')->collapsible(false),
                NavigationGroup::make('Inventario')->collapsible(false),
                NavigationGroup::make('Reportes')->collapsible(false),
                NavigationGroup::make('Administración')->collapsible(false),
            ])
            // Búsqueda global (Ctrl/Cmd + K) por placa, cliente u orden.
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->globalSearchDebounce('250ms')
            ->userMenuItems([
                'profile' => Action::make('profile')
                    ->label('Mi perfil')
                    ->icon(Heroicon::OutlinedUser),
                'logout' => fn (Action $action) => $action
                    ->label('Cerrar sesión')
                    ->color('danger'),
            ])
            // Estado de la caja junto a la búsqueda.
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => view('filament.topbar.caja'),
            )
            // Cabecera del menú de usuario (nombre, correo, rol) y recordatorio de caja abierta.
            ->renderHook(
                PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
                fn () => view('filament.user-menu.cabecera'),
            )
            // Barra de navegación inferior del celular (solo se ve bajo 1024 px).
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.mobile.nav'),
            )
            // Campanita del topbar — usada para avisarle al cajero cuando un cliente
            // responde por WhatsApp (ver WebhookController::whatsapp).
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
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
