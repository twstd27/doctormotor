<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a ciertos roles: `->middleware('rol:cajero,operador_tecnico')`.
 * El super_admin siempre pasa — la tabla de docs/03-API-ENDPOINTS.md lista quién puede
 * llamar cada endpoint "además de super_admin".
 */
class EnsureRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $rol = $request->user()?->rol;

        if ($rol !== 'super_admin' && ! in_array($rol, $roles, true)) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        return $next($request);
    }
}
