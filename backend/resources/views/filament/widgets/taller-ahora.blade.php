<x-filament-widgets::widget>
    <section class="dm-panel dm-taller" aria-labelledby="dm-taller-t">
        <div class="dm-panel-cabecera">
            <h2 id="dm-taller-t" class="dm-panel-titulo">
                Taller ahora
                <span class="dm-panel-nota">{{ $total }} {{ $total === 1 ? 'vehículo' : 'vehículos' }}</span>
            </h2>
            <a class="dm-panel-enlace" href="{{ \App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource::getUrl('index') }}">Ver todas las órdenes →</a>
        </div>

        <div class="dm-taller-scroll">
            <div class="dm-taller-grid">
                @foreach ($columnas as $columna)
                    <div @class(['dm-taller-col', 'dm-taller-col--listo' => $columna['estado'] === 'listo_entrega'])>
                        <div class="dm-taller-col-cab" style="color: {{ $columna['color'] }}">
                            <span class="dm-taller-col-nombre"><i style="background: {{ $columna['color'] }}"></i>{{ $columna['nombre'] }}</span>
                            <span class="dm-mono">{{ $columna['ordenes']->count() }}</span>
                        </div>

                        @forelse ($columna['ordenes'] as $orden)
                            @php
                                $entrega = $orden->fecha_entrega_estimada;
                                $vencida = $entrega && $entrega->isPast() && ! $entrega->isToday();
                                $tecnico = $orden->tecnicoAsignado
                                    ? collect(explode(' ', trim($orden->tecnicoAsignado->nombre)))->first().(str_contains(trim($orden->tecnicoAsignado->nombre), ' ') ? ' '.mb_substr(trim(explode(' ', trim($orden->tecnicoAsignado->nombre), 2)[1]), 0, 1).'.' : '')
                                    : null;
                                $saldo = $orden->saldoPendiente();
                            @endphp
                            <a href="{{ \App\Filament\Resources\OrdenesTrabajo\OrdenTrabajoResource::getUrl('view', ['record' => $orden]) }}"
                               @class(['dm-vc', 'dm-vc--vencida' => $vencida])>
                                <x-dm.placa>{{ $orden->vehiculo?->placa }}</x-dm.placa>
                                <div class="dm-vc-titulo">{{ $orden->vehiculo?->marca }} {{ $orden->vehiculo?->modelo }}</div>
                                <div @class(['dm-vc-sub', 'dm-vc-sub--rojo' => $vencida, 'dm-vc-sub--ambar' => ! $vencida && $orden->estado === 'listo_entrega' && $saldo > 0.009])>
                                    @if ($vencida)
                                        Vencida{{ $tecnico ? ' · '.$tecnico : '' }}
                                    @elseif ($orden->estado === 'listo_entrega' && $saldo > 0.009)
                                        Saldo Bs {{ number_format($saldo, 2, ',', '.') }}
                                    @else
                                        {{ $entrega ? ucfirst($entrega->translatedFormat('D d')).' · ' : '' }}{{ $tecnico ?? 'Sin técnico' }}
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="dm-taller-vacio">Sin vehículos</div>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
