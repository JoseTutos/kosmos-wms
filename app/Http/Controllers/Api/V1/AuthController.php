<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CambiarPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Seguridad\AutenticacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


/** Sesión de la app Flutter. Toda la lógica vive en AutenticacionService (la misma del login web). */
class AuthController extends Controller
{
    public function __construct(private readonly AutenticacionService $auth) {}

    /** POST /api/v1/auth/login → 200 { data: { token, tipo, expira_en, usuario } } */
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = $this->auth->autenticar(
            $request->validated('email'),
            $request->validated('password'),
            'api',
        );

        $resultado = $this->auth->emitirToken($usuario, $request->validated('dispositivo'));

        return response()->json(['data' => [
            'token'     => $resultado->plainTextToken,
            'tipo'      => 'Bearer',
            'expira_en' => $resultado->accessToken->expires_at?->toIso8601String(),
            'usuario'   => UsuarioResource::make($usuario)->resolve($request),
        ]]);
    }

    /** GET /api/v1/auth/me → la app sabe si debe forzar el cambio de clave (debe_cambiar_password). */
    public function me(Request $request): UsuarioResource
    {
        return UsuarioResource::make($request->user());
    }

    /** POST /api/v1/auth/logout → 204. Revoca solo el token de este dispositivo. */
    public function logout(Request $request): Response
    {
        $this->auth->cerrarSesionApi($request->user());

        return response()->noContent();
    }

    /** PUT /api/v1/auth/password → cambia la clave (temporal o voluntaria) y conserva solo este token. */
    public function password(CambiarPasswordRequest $request): UsuarioResource
    {
        $usuario = $request->user();

        $this->auth->cambiarPassword(
            $usuario,
            $request->validated('password_actual'),
            $request->validated('password'),
            $usuario->currentAccessToken()->getKey(),
        );

        return UsuarioResource::make($usuario->refresh());
    }
}
