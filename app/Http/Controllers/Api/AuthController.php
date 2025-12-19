<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente.',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
                'data' => $result
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el inicio de sesión.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentTokenId = $request->user()->currentAccessToken()?->id;

            $this->authService->logout($user, $currentTokenId);

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh user token.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->authService->refreshToken($user);

            return response()->json([
                'success' => true,
                'message' => 'Token renovado exitosamente.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al renovar token.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getProfile($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Perfil obtenido exitosamente.',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user info (for middleware 'auth:sanctum').
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
            'message' => 'Usuario autenticado.'
        ]);
    }
}
