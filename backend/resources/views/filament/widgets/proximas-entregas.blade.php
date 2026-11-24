<x-filament-widgets::widget>
    <section class="dm-panel">
        <h2 class="dm-panel-titulo">Próximas entregas</h2>

        @if ($entregas->isEmpty())
            <p class="dm-ingreso-problema">No hay entregas con fecha prometida. Pon la fecha estimada al recepcionar un vehículo y aparecerá acá.</p>
        @else
            <ul class="dm-entregas">
                @foreach ($entregas as $orden)
                    <li>
                        <a href="{{ \App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource::getUrl('view', ['record' => $orden]) }}">
                            <span @class(['dm-mono', 'dm-entregas-fecha', 'dm-entregas-fecha--hoy' => $orden->fecha_entrega_estimada->isToday()])>
                                {{ $orden->fecha_entrega_estimada->isToday() ? 'Hoy' : ucfirst($orden->fecha_entrega_estimada->translatedFormat('D d')) }}
                            </span>
                            <x-dm.placa>{{ $orden->vehiculo?->placa }}</x-dm.placa>
                            <span class="dm-entregas-cliente">{{ $orden->cliente?->nombre }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-filament-widgets::widget>
