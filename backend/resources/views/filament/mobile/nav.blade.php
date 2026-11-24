@php
    use App\Filament\Pages\CajaDelDia;
    use App\Filament\Pages\Dashboard;
    use App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource;

    $usuario = filament()->auth()->user();
@endphp

@if ($usuario)
    <nav class="dm-nav-movil" aria-label="Navegación principal">
        <a href="{{ Dashboard::getUrl() }}" @class(['dm-nav-item', 'dm-nav-item--activo' => request()->routeIs('filament.admin.pages.dashboard')])>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg>
            Inicio
        </a>

        @if (OrdenTrabajoResource::canViewAny())
            <a href="{{ OrdenTrabajoResource::getUrl('index') }}" @class(['dm-nav-item', 'dm-nav-item--activo' => request()->routeIs('filament.admin.resources.ordenes-trabajo.index', 'filament.admin.resources.ordenes-trabajo.view', 'filament.admin.resources.ordenes-trabajo.edit')])>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>
                Órdenes
            </a>
        @endif

        @if (OrdenTrabajoResource::canCreate())
            <a href="{{ OrdenTrabajoResource::getUrl('create') }}" class="dm-nav-fab" aria-label="Recepcionar un vehículo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            </a>
        @endif

        @if (CajaDelDia::canAccess())
            <a href="{{ CajaDelDia::getUrl() }}" @class(['dm-nav-item', 'dm-nav-item--activo' => request()->routeIs('filament.admin.pages.caja-del-dia')])>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                Caja
            </a>
        @endif

        {{-- "Más" abre el menú lateral de siempre, que en el celular es un cajón. --}}
        <button type="button" class="dm-nav-item" x-data x-on:click="$store.sidebar.open()" aria-label="Abrir el menú completo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            Más
        </button>
    </nav>
@endif
