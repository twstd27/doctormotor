<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
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
     * Por ahora solo se registran — procesarlos (ej. marcar Notificacion como leída,
     * o reaccionar a un "Sí"/"No" de aprobación por texto libre) queda para cuando haya
     * credenciales reales para probar el flujo completo.
     */
    public function whatsapp(Request $request)
    {
        Log::info('[whatsapp-webhook] Evento entrante', $request->all());

        return response()->json(['status' => 'ok']);
    }
}
