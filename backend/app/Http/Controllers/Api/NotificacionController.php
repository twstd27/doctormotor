<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Notificacion::query()
                ->when($request->string('estado')->toString(), fn ($q, $e) => $q->where('estado', $e))
                ->orderByDesc('created_at')
                ->paginate(30),
        );
    }

    public function reintentar(Notificacion $notificacion): JsonResponse
    {
        $this->whatsApp->despachar($notificacion);

        return response()->json(['data' => $notificacion->fresh()]);
    }

    public function misNotificaciones(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Notificacion::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }
}
