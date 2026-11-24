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
                // Acento verde Razer del sistema (#44D62C = 500). El texto sobre lima es oscuro: ver theme.css.
                'primary' => [
                    50 => '236, 252, 232', 100 => '211, 249, 196',
                    200 => '173, 246, 149', 300 => '129, 241, 97',
                    400 => '90, 224, 57', 500 => '68, 214, 44',
                    600 => '46, 178, 27', 700 => '41, 137, 26',
                    800 => '35, 103, 24', 900 => '35, 79, 25',
                    950 => '13, 42, 6',
                ],
                // Grafito: 950 = fondo de página, 900 = tarjetas, 800 = botones secundarios, 700 = bordes.
                'gray' => [
                    50 => '242, 244, 239', 100 => '234, 238, 234',
                    200 => '208, 212, 209', 300 => '173, 177, 174',
                    400 => '128, 131, 128', 500 => '99, 102, 99',
                    600 => '73, 76, 74', 700 => '55, 58, 56',
                    800 => '29, 32, 29', 900 => '21, 24, 21',
                    950 => '13, 16, 13',
                ],
                'success' => Color::hex('#44D62C'),
                'warning' => Color::hex('#FCAA2B'),
                'danger' => [
                    50 => '255, 241, 243', 100 => '255, 225, 229',
                    200 => '255, 194, 202', 300 => '255, 148, 162',
                    400 => '245, 104, 123', 500 => '236, 72, 92',
                    600 => '211, 47, 68', 700 => '171, 35, 54',
                    800 => '127, 29, 43', 900 => '94, 24, 34',
                    950 => '58, 18, 25',
                ],
                'info' => Color::hex('#44A8E7'),
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
