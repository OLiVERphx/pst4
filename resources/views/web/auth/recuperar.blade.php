@extends('layouts.web')

@section('title', 'Recuperar contraseña')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="brand-icon">SW</span>
      <div class="brand-name">SmartPhone World</div>
    </div>

    <h2 class="auth-title">¿Olvidaste tu contraseña?</h2>
    <p class="auth-sub">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.</p>

    @if(session('status'))
      <div role="status" style="background:rgba(5,150,105,.1);border:1px solid rgba(5,150,105,.4);color:#10B981;border-radius:.6rem;padding:.7rem .9rem;font-size:.88rem;margin-bottom:1rem">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
      @csrf
      <div class="form-group">
        <label class="form-label" for="email">Correo electrónico</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required />
        @error('email')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <x-turnstile />
      </div>

      <div class="form-group">
        <button type="submit" class="form-submit">Enviar enlace</button>
      </div>
    </form>

    <div class="auth-switch"><a href="{{ route('web.login') }}">← Volver a iniciar sesión</a></div>
  </div>
</div>
@endsection
