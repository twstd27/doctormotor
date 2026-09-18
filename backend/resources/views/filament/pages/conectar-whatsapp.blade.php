<x-filament-panels::page>
    @if (! $this->configuracionCompleta())
        <div class="rounded-2xl border border-danger-600 bg-danger-500/10 p-4 text-sm text-danger-400">
            Falta configurar <code>META_APP_ID</code>, <code>META_APP_SECRET</code> o
            <code>WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID</code> en el <code>.env</code> del servidor.
        </div>
    @else
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6">
            <p class="text-sm text-gray-400">
                Al conectar, Meta te va a mandar un mensaje dentro de la app de WhatsApp Business
                del celular del taller — ábrelo y confirma ahí antes de que se cierre esta ventana
                (el código que genera dura solo 30 segundos una vez que confirmas).
            </p>

            <button
                type="button"
                onclick="iniciarEmbeddedSignup()"
                id="btn-conectar-whatsapp"
                class="mt-4 inline-flex h-11 items-center rounded-lg bg-primary-500 px-4 text-sm font-semibold text-gray-950 disabled:opacity-60"
            >
                Conectar WhatsApp del taller
            </button>

            <p id="estado-conexion" class="mt-3 text-sm text-gray-400"></p>
        </div>

        @if ($wabaId)
            <div class="mt-4 rounded-2xl border border-success-600 bg-success-500/10 p-4 text-sm">
                <p class="font-semibold text-success-400">Conectado</p>
                <p class="mt-1 text-gray-300">WABA ID: <code>{{ $wabaId }}</code></p>
                <p class="text-gray-300">Phone Number ID: <code>{{ $phoneNumberId }}</code></p>
                <p class="mt-2 text-gray-400">
                    Copia el Phone Number ID en <code>WHATSAPP_PHONE_NUMBER_ID</code> del <code>.env</code>
                    si es distinto al que ya tenías, y corre <code>php artisan config:cache</code>.
                </p>
            </div>
        @endif

        @if ($error)
            <div class="mt-4 rounded-2xl border border-danger-600 bg-danger-500/10 p-4 text-sm text-danger-400">
                {{ $error }}
            </div>
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
</x-filament-panels::page>
