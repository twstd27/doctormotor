@php
    $appUrl = rtrim((string) config('services.frontend.url'), '/');
@endphp

<div class="dm-login">
    {{-- Izquierda: marca, tacómetro y titular. En el celular queda arriba, más chico. --}}
    <section class="dm-login-marca" aria-label="Doctor Motor">
        <div class="dm-login-logo">
            <span class="dm-login-logo-icono" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0B0D10" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>
            </span>
            <span class="dm-login-logo-nombre">Doctor Motor</span>
            <span class="dm-login-logo-etiqueta">TALLER · ADMIN</span>
        </div>

        <div class="dm-login-centro">
            <div class="dm-login-reloj">
                <div class="dm-login-reloj-brillo" aria-hidden="true"></div>
                @include('filament.auth.tacometro')
                <div class="dm-login-reloj-lectura">
                    <div class="dm-login-reloj-cifra">0.9</div>
                    <div class="dm-login-reloj-estado"><span></span>Motor en ralentí</div>
                </div>
            </div>

            <div class="dm-login-texto">
                <h1 class="dm-rise">Cada auto,<br><span>en tiempo real.</span></h1>
                <p class="dm-rise dm-d1 dm-login-lema">Recepción, diagnóstico, presupuesto, cobro y entrega. Todo el taller en una sola pantalla.</p>
                <ol class="dm-rise dm-d2 dm-login-flujo" aria-label="Flujo de una orden">
                    <li><span style="background:#A3ACB8"></span><i></i></li>
                    <li><span style="background:#6CB4FF"></span><i></i></li>
                    <li><span style="background:#FFB547"></span><i></i></li>
                    <li><span style="background:#C5F82A"></span><i></i></li>
                    <li><span style="background:#B9A2FF"></span><i></i></li>
                    <li><span class="dm-login-flujo-fin"></span></li>
                </ol>
                <div class="dm-rise dm-d2 dm-login-flujo-nota">RECEPCIÓN → ENTREGA</div>
            </div>
        </div>

        <div class="dm-login-placas">
            <x-dm.placa>2847-KDR</x-dm.placa><x-dm.placa>5120-BNX</x-dm.placa><x-dm.placa>4519-PLM</x-dm.placa>
            <span>y los que vengan.</span>
        </div>
    </section>

    {{-- Derecha: el formulario (en el celular, hoja inferior). --}}
    <section class="dm-login-panel">
        <div class="dm-login-form">
            <div class="dm-rise">
                <div class="dm-login-eyebrow">BIENVENIDO DE VUELTA</div>
                <h2>Entra a tu taller</h2>
                <p>Usa el correo con el que te dieron acceso.</p>
            </div>

            <div class="dm-rise dm-d1 dm-login-campos">
                {{ $this->content }}
            </div>

            @if ($appUrl !== '')
                <div class="dm-rise dm-d3 dm-login-app">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9AA3AE" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/></svg>
                    <div>¿Eres técnico o cliente? <a href="{{ $appUrl }}">Entra desde la app</a></div>
                </div>
            @endif

            <div class="dm-rise dm-d3 dm-login-pie">Doctor Motor · Mustang's Garage</div>
        </div>
    </section>
</div>
