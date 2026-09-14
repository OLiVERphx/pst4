<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServicioAuth;
use App\Services\ServicioAuditoria;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected ServicioAuth $authService;

    public function __construct(ServicioAuth $auth)
    {
        $this->authService = $auth;
    }

    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(LoginRequest $request)
    {
        $creds = $request->validated();

        try {
            $result = $this->authService->login($creds);

            // Login en sesión web
            Auth::login($result['user']);

            // Auditoría de inicio de sesión exitoso
            ServicioAuditoria::registrar(
                'login.exitoso',
                $result['user'],
                null,
                ['email' => $result['user']->email, 'rol' => $result['user']->role?->nombre],
                $result['user']->id
            );

            return redirect()->route('admin.dashboard');
        } catch (\Throwable $e) {
            // Auditoría de intento de inicio de sesión fallido
            ServicioAuditoria::registrar(
                'login.fallido',
                null,
                null,
                ['email' => $creds['email'] ?? null, 'motivo' => $e->getMessage()]
            );

            return back()->withErrors(['email' => 'Credenciales inválidas o usuario inactivo.'])->withInput();
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            ServicioAuditoria::registrar(
                'logout',
                $user,
                null,
                ['email' => $user->email],
                $user->id
            );
            $this->authService->logout($user);
        }
        return redirect()->route('admin.login');
    }
}
