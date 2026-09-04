<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    /**
     * Login con email o teléfono + contraseña.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['login'])
            ->orWhere('telefono_whatsapp', $data['login'])
            ->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'login' => ['Las credenciales no coinciden con ningún registro.'],
            ]);
        }

        return $this->issueTokenResponse($user);
    }

    /**
     * Solicita un enlace mágico de acceso por WhatsApp.
     *
     * El envío pasa por WhatsAppService — con credenciales reales de Meta configuradas
     * (WHATSAPP_TOKEN) manda el mensaje de verdad; sin ellas, cae a un log local. En
     * entorno local, además se devuelve el token en la respuesta para poder probar el
     * flujo completo sin depender de tener WhatsApp real conectado.
     */
    public function loginWhatsappLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telefono_whatsapp' => ['required', 'string'],
        ]);

        $user = User::where('telefono_whatsapp', $data['telefono_whatsapp'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'No se encontró una cuenta con ese número de WhatsApp.',
            ], 404);
        }

        $token = Str::random(48);
        Cache::put("whatsapp_login:{$token}", $user->id, now()->addMinutes(10));

        $this->whatsApp->enviarPlantilla(
            telefono: $user->telefono_whatsapp,
            plantilla: 'enlace_acceso',
            // Apunta al FRONTEND (la SPA es la que hace el POST de canje), no a la API
            // directo — un enlace a una ruta POST-only no funciona al hacer clic.
            parametros: ['link' => config('services.frontend.url')."/auth/whatsapp/{$token}"],
            userId: $user->id,
        );

        return response()->json([
            'message' => 'Enlace de acceso enviado por WhatsApp.',
            'debug_token' => app()->environment('local') ? $token : null,
        ]);
    }

    /**
     * Canjea el token del enlace mágico de WhatsApp por una sesión.
     */
    public function verifyWhatsapp(string $token): JsonResponse
    {
        $userId = Cache::pull("whatsapp_login:{$token}");

        if (! $userId) {
            return response()->json([
                'message' => 'El enlace expiró o ya fue utilizado.',
            ], 410);
        }

        $user = User::findOrFail($userId);

        return $this->issueTokenResponse($user);
    }

    /**
     * Datos básicos para pintar la pantalla de invitación (nombre a mostrar) antes de
     * que el técnico defina su contraseña. No consume el token — eso lo hace
     * `aceptarInvitacion`, así el técnico puede recargar la pantalla sin perder el enlace.
     */
    public function invitacionTecnico(string $token): JsonResponse
    {
        $userId = Cache::get("invitacion_tecnico:{$token}");

        if (! $userId) {
            return response()->json([
                'message' => 'La invitación expiró o ya fue utilizada.',
            ], 410);
        }

        $user = User::findOrFail($userId);

        return response()->json(['data' => ['nombre' => $user->nombre, 'rol' => $user->rol]]);
    }

    /**
     * Canjea la invitación: define la contraseña, activa la cuenta y entrega una sesión.
     */
    public function aceptarInvitacion(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $userId = Cache::pull("invitacion_tecnico:{$token}");

        if (! $userId) {
            return response()->json([
                'message' => 'La invitación expiró o ya fue utilizada.',
            ], 410);
        }

        $user = User::findOrFail($userId);
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'activo' => true,
            'email_verified_at' => now(),
        ])->save();

        return $this->issueTokenResponse($user);
    }

    /**
     * URL de autorización de Google para que el frontend redirija al usuario.
     */
    public function googleRedirect(): JsonResponse
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * Recibe el `code` de Google, crea o vincula la cuenta, y devuelve un token de sesión.
     */
    public function googleCallback(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $user) {
            $user = User::create([
                'nombre' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Cliente',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'rol' => 'cliente',
                'activo' => true,
            ]);
        } elseif (! $user->google_id) {
            $user->update(['google_id' => $googleUser->getId()]);
        }

        return $this->issueTokenResponse($user);
    }

    /**
     * Revoca el token actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * Perfil del usuario autenticado + rol/permisos.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'permissions');

        return response()->json(['data' => $user]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Enlace de recuperación enviado.'
                : 'No se pudo enviar el enlace de recuperación.',
        ], $status === Password::RESET_LINK_SENT ? 200 : 422);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            event(new PasswordReset($user));
        });

        return response()->json([
            'message' => $status === Password::PASSWORD_RESET
                ? 'Contraseña actualizada.'
                : 'No se pudo restablecer la contraseña.',
        ], $status === Password::PASSWORD_RESET ? 200 : 422);
    }

    private function issueTokenResponse(User $user): JsonResponse
    {
        $user->forceFill(['ultimo_login_at' => now()])->save();

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $user->createToken('api')->plainTextToken,
            ],
        ]);
    }
}
