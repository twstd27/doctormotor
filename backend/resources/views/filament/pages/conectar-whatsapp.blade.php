<x-filament-panels::page>
    @php
        $conectado = app(\App\Services\WhatsAppService::class)->configurado();
    @endphp

    <div class="dm-pila">
        {{-- Estado actual --}}
        <section class="dm-panel">
            <div class="dm-panel-cabecera">
                <h2 class="dm-panel-titulo">Estado de la conexión</h2>
                @if ($conectado)
                    <span class="dm-estado dm-estado--en_reparacion"><i aria-hidden="true"></i>Conectado</span>
                @else
                    <span class="dm-estado dm-estado--esperando_aprobacion"><i aria-hidden="true"></i>Sin conectar</span>
                @endif
            </div>
            <p class="dm-ingreso-problema">
                @if ($conectado)
                    El sistema ya tiene las credenciales de WhatsApp Business. Si los mensajes empiezan a fallar con
                    «la conexión venció», vuelve a conectar el número desde acá.
                @else
                    Todavía no hay credenciales de WhatsApp en el servidor: los mensajes se arman listos para mandarlos a
                    mano desde tu WhatsApp. Conecta el número para que salgan solos.
                @endif
            </p>
        </section>

        @if (! $this->configuracionCompleta())
            <div class="dm-aviso dm-aviso--rojo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                <span>Falta configurar <code>META_APP_ID</code>, <code>META_APP_SECRET</code> o
                    <code>WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID</code> en el <code>.env</code> del servidor.</span>
            </div>
        @else
            <section class="dm-panel">
                <div>
                    <h2 class="dm-panel-titulo">Conectar el número del taller</h2>
                    <p class="dm-celda-sub">Se hace una vez, y de nuevo solo si la conexión vence.</p>
                </div>

                <ol class="dm-pasos">
                    <li>
                        <span class="dm-num">1</span>
                        <div>
                            <div class="dm-celda-titulo dm-celda-titulo--normal">Abre la conexión de Meta</div>
                            <div class="dm-celda-sub">Se abre una ventana de Facebook para elegir el número.</div>
                        </div>
                    </li>
                    <li>
                        <span class="dm-num dm-num--gris">2</span>
                        <div>
                            <div class="dm-celda-titulo dm-celda-titulo--normal">Confirma en el celular del taller</div>
                            <div class="dm-celda-sub">Meta manda un mensaje dentro de la app de WhatsApp Business: ábrelo y confirma antes de que se cierre la ventana (el código dura solo 30 segundos una vez que confirmas).</div>
                        </div>
                    </li>
                    <li>
                        <span class="dm-num dm-num--gris">3</span>
                        <div>
                            <div class="dm-celda-titulo dm-celda-titulo--normal">Copia los datos al servidor</div>
                            <div class="dm-celda-sub">Al terminar aparecen abajo el WABA ID y el Phone Number ID.</div>
                        </div>
                    </li>
                </ol>

                <div>
                    <button
                        type="button"
                        onclick="iniciarEmbeddedSignup()"
                        id="btn-conectar-whatsapp"
                        class="dm-btn dm-btn--primario"
                    >
                        {{ $conectado ? 'Reconectar WhatsApp del taller' : 'Conectar WhatsApp del taller' }}
                    </button>
                    <p id="estado-conexion" class="dm-celda-sub dm-estado-conexion"></p>
                </div>
            </section>

            @if ($wabaId)
                <div class="dm-aviso dm-aviso--ok dm-aviso--bloque">
                    <div>
                        <strong>Conectado.</strong>
                        <div>WABA ID: <code>{{ $wabaId }}</code></div>
                        <div>Phone Number ID: <code>{{ $phoneNumberId }}</code></div>
                        <div class="dm-celda-sub">
                            Copia el Phone Number ID en <code>WHATSAPP_PHONE_NUMBER_ID</code> del <code>.env</code>
                            si es distinto al que ya tenías, y corre <code>php artisan config:cache</code>.
                        </div>
                    </div>
                </div>
            @endif

            @if ($error)
                <div class="dm-aviso dm-aviso--rojo">{{ $error }}</div>
            @endif

            <div id="fb-root"></div>
            <script>
                window.fbAsyncInit = function () {
                    FB.init({
                        appId: '{{ $this->appId() }}',
                        autoLogAppEvents: true,
                        xfbml: true,
                        version: 'v21.0',
                        // Sin esto, Chrome intercepta el login con su propio flujo nativo de
                        // FedCM (scope=openid, response_type=token, redirect_uri genérico a la
                        // raíz del dominio) ignorando el config_id del Embedded Signup — por
                        // eso salía "URL bloqueada" apuntando a una URL que nunca configuramos.
                        fedCM: false,
                    });
                };

                let ultimoWabaId = null;
                let ultimoPhoneNumberId = null;

                window.addEventListener('message', (event) => {
                    if (!event.origin.endsWith('facebook.com')) return;
                    try {
                        const data = JSON.parse(event.data);
                        if (data.type === 'WA_EMBEDDED_SIGNUP' && data.data) {
                            ultimoWabaId = data.data.waba_id ?? ultimoWabaId;
                            ultimoPhoneNumberId = data.data.phone_number_id ?? ultimoPhoneNumberId;
                        }
                    } catch (e) {
                        // Mensajes de otros orígenes/formatos de facebook.com que no son de este flujo — se ignoran.
                    }
                });

                window.iniciarEmbeddedSignup = function () {
                    const estado = document.getElementById('estado-conexion');
                    estado.textContent = 'Abriendo la ventana de conexión de Meta…';

                    FB.login(function (response) {
                        const code = response?.authResponse?.code;

                        if (!code || !ultimoWabaId || !ultimoPhoneNumberId) {
                            estado.textContent = 'No se completó la conexión (se cerró la ventana o falta confirmar en la app de WhatsApp Business).';
                            return;
                        }

                        estado.textContent = 'Terminando la conexión con el servidor…';
                        @this.call('completarConexion', code, ultimoWabaId, ultimoPhoneNumberId)
                            .then(() => { estado.textContent = 'Listo — revisa el resultado abajo.'; });
                    }, {
                        config_id: '{{ $this->configId() }}',
                        response_type: 'code',
                        override_default_response_type: true,
                        extras: { setup: {} },
                    });
                };
            </script>
            <script async defer crossorigin="anonymous" src="https://connect.facebook.net/es_LA/sdk.js"></script>
        @endif
    </div>
</x-filament-panels::page>
