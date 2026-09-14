@extends('layouts.web')

@section('title', 'Iniciar sesión')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="brand-icon">SW</span>
      <div class="brand-name">SmartPhone World</div>
    </div>

    <h2 class="auth-title">Bienvenido de vuelta</h2>
    <p class="auth-sub">Inicia sesión para acceder a tu cuenta y gestionar tus pedidos.</p>

    @if(session('error'))
      <div style="color:#EF4444;font-size:.9rem;margin-bottom:.6rem">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('web.login') }}" novalidate>
      @csrf

      <div class="form-group">
        <label class="form-label" for="email">Correo electrónico</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required />
        @error('email')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input class="form-input" id="password" name="password" type="password" required />
        @error('password')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label><input type="checkbox" name="recuerdar" {{ old('recuerdar') ? 'checked' : '' }}> Recordarme</label>
      </div>

      <div class="form-group">
        <button type="submit" class="form-submit">Iniciar sesión</button>
      </div>
    </form>

    <div class="auth-switch">¿No tienes cuenta? <a href="{{ route('web.register') }}">Regístrate</a></div>
  </div>
</div>
@endsection
