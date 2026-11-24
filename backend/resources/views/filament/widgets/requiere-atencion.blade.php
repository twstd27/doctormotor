<x-filament-widgets::widget>
    <section class="dm-seccion" aria-labelledby="dm-atencion-t">
        <h2 id="dm-atencion-t" class="dm-seccion-titulo">
            Requiere tu atención
            @if (count($tarjetas) > 0)
                <span class="dm-contador dm-contador--ambar">{{ count($tarjetas) }}</span>
            @endif
        </h2>

        @if (count($tarjetas) === 0)
            <div class="dm-panel dm-atencion-vacia">
                <span class="dm-dot dm-dot--lima" aria-hidden="true"></span>
                Todo al día: no hay entregas vencidas, presupuestos sin respuesta ni stock bajo.
            </div>
        @else
            <div class="dm-atencion">
                @foreach ($tarjetas as $tarjeta)
                    <div class="dm-atencion-tarjeta dm-atencion-tarjeta--{{ $tarjeta['color'] }}">
                        <div class="dm-atencion-titulo">{{ $tarjeta['titulo'] }}</div>
                        <div class="dm-atencion-texto">
                            {{ $tarjeta['texto'] }}
                            @if ($tarjeta['orden'])
                                <x-dm.placa>{{ $tarjeta['orden']->vehiculo?->placa }}</x-dm.placa>
                            @endif
                        </div>
                        @if ($tarjeta['mas'])
                            <div class="dm-celda-sub">{{ $tarjeta['mas'] }}</div>
                        @endif
                        <a class="dm-atencion-accion" href="{{ $tarjeta['url'] }}" @if ($tarjeta['externa']) target="_blank" rel="noopener" @endif>{{ $tarjeta['accion'] }}</a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-filament-widgets::widget>
