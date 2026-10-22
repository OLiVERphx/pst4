@extends('layouts.web')

@section('title', 'Nueva contraseña')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="brand-icon">SW</span>
      <div class="brand-name">SmartPhone World</div>
    </div>

    <h2 class="auth-title">Crea tu nueva contraseña</h2>
    <p class="auth-sub">Al guardarla se cerrará tu sesión en los demás dispositivos.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="form-group">
        <label class="form-label" for="email">Correo electrónico</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required />
        @error('email')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Nueva contraseña</label>
        <input class="form-input" id="password" name="password" type="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" required />
        @error('password')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirmation">Repite la contraseña</label>
        <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required />
      </div>

      <div class="form-group">
        <button type="submit" class="form-submit">Guardar contraseña</button>
      </div>
    </form>
  </div>
</div>
@endsection
