<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\ServicioAuth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, ServicioAuth $authService): JsonResponse
    {
        $user = $authService->register($request->validated());

        return response()->json([
            'user' => $user,
            'message' => 'Registro completado correctamente.',
        ]);
    }

    public function login(LoginRequest $request, ServicioAuth $authService): JsonResponse
    {
        $result = $authService->login($request->validated());

        return response()->json([
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request, ServicioAuth $authService): JsonResponse
    {
        $authService->logout($request->user());

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
