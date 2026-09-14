<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ServicioAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected ServicioAuth $authService;

    public function __construct(ServicioAuth $auth)
    {
        $this->authService = $auth;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());
        return response()->json(['user' => $user, 'message' => 'Registered'], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());
        // Generar token solo para el endpoint API
        $token = $result['user']->createToken('api-token')->plainTextToken;
        return response()->json(['user' => $result['user'], 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $this->authService->logout($user);
        }
        return response()->json(['message' => 'Logged out']);
    }
}
