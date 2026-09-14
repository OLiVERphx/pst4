<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Mostrar formulario de login
    public function showLogin()
    {
        return view('web.auth.login');
    }

    // Mostrar formulario de registro
    public function showRegister()
    {
        return view('web.auth.register');
    }

    // Procesar el intento de autenticación
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('recuerdar')) ) {
            $request->session()->regenerate();
            return redirect()->intended(route('web.home'));
        }

        return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->withInput();
    }

    // Registrar un nuevo cliente
    public function register(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:8|confirmed',
            'cedula'    => 'nullable|string|max:20|unique:users,cedula',
            'telefono'  => 'nullable|string|max:20',
            'ciudad'    => 'nullable|string|max:80',
            'direccion' => 'nullable|string|max:500',
        ]);

        $rolCliente = Role::where('nombre', 'cliente')->first();

        $user = User::create([
            'name'      => $request->name,
            'apellido'  => $request->apellido,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'cedula'    => $request->cedula,
            'telefono'  => $request->telefono,
            'ciudad'    => $request->ciudad,
            'direccion' => $request->direccion,
            'rol_id'    => $rolCliente?->id,
            'activo'    => true,
            'estado'    => 'Trujillo',
        ]);

        Auth::login($user);

        return redirect()->route('web.home');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('web.home');
    }
}
