<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    /**
     * Meta verifica el webhook con un GET + hub.challenge antes de empezar a mandar eventos.
     */
    public function verificarWhatsapp(Request $request)
    {
        if (
            $request->get('hub_mode') === 'subscribe'
            && $request->get('hub_verify_token') === config('services.whatsapp.verify_token')
        ) {
            return response($request->get('hub_challenge'), 200);
        }

        return response('Token de verificación inválido.', 403);
    }

    /**
     * Eventos entrantes: respuestas del cliente y confirmaciones de entrega/lectura.
     * No hay ningún bot que le conteste al cliente (eso es un feature aparte, no
     * construido) — lo único que hace esto es avisarle al cajero/admin, desde el panel,
     * que alguien respondió, para que lo revisen a mano desde el celular de WhatsApp
     * Business del taller.
     */
    public function whatsapp(Request $request)
    {
        $payload = $request->all();
        Log::info('[whatsapp-webhook] Evento entrante', $payload);

        try {
            $this->avisarRespuestasEntrantes($payload);
        } catch (\Throwable $e) {
            // Nunca se deja que un payload raro tumbe el ack — Meta desactiva el webhook
            // si le fallan demasiadas veces seguidas.
            Log::error('[whatsapp-webhook] No se pudo procesar el evento entrante', [
                'mensaje' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function avisarRespuestasEntrantes(array $payload): void
    {
        $mensajes = collect($payload['entry'] ?? [])
            ->flatMap(fn (array $entry) => $entry['changes'] ?? [])
            ->flatMap(fn (array $cambio) => $cambio['value']['messages'] ?? []);

        if ($mensajes->isEmpty()) {
            // Sin "messages" es un evento de estado (entregado/leído), no una respuesta real.
            return;
        }

        $destinatarios = User::whereIn('rol', ['cajero', 'super_admin'])->get();
        if ($destinatarios->isEmpty()) {
            return;
        }

        foreach ($mensajes as $mensaje) {
            $this->notificarUnMensaje($mensaje, $destinatarios);
        }
    }

    private function notificarUnMensaje(array $mensaje, \Illuminate\Support\Collection $destinatarios): void
    {
        $numeroMeta = $mensaje['from'] ?? null;
        if (! $numeroMeta) {
            return;
        }

        $texto = $mensaje['text']['body'] ?? match ($mensaje['type'] ?? null) {
            'image' => '[imagen]',
            'audio' => '[audio]',
            'video' => '[video]',
            'document' => '[documento]',
            'sticker' => '[sticker]',
            'location' => '[ubicación]',
            default => '[mensaje sin texto]',
        };

        $cliente = $this->buscarClientePorNumero($numeroMeta);
        $quien = $cliente ? "{$cliente->nombre} ({$numeroMeta})" : "un número no registrado ({$numeroMeta})";

        Notification::make()
            ->title('Respuesta de WhatsApp')
            ->body("{$quien} escribió: \"".Str::limit($texto, 200).'"')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('info')
            ->sendToDatabase($destinatarios);
    }

    /**
     * Meta manda el número siempre en formato internacional completo (ej. "59170099887"),
     * pero `clientes.telefono_whatsapp` puede tener guardado el mismo número en cualquiera
     * de las formas que acepta normalizarTelefono() — se prueban las variantes razonables.
     */
    private function buscarClientePorNumero(string $numeroMeta): ?Cliente
    {
        $completo = $this->whatsApp->normalizarTelefono($numeroMeta);
        $local = preg_replace('/^591/', '', $completo);

        return Cliente::where('telefono_whatsapp', $completo)
            ->orWhere('telefono_whatsapp', $local)
            ->orWhere('telefono_whatsapp', '+'.$completo)
            ->first();
    }
}
