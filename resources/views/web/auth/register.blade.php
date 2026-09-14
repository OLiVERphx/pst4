@extends('layouts.web')

@section('title', 'Crear cuenta')

@section('content')
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="brand-icon">SW</span>
      <div class="brand-name">SmartPhone World</div>
    </div>

    <h2 class="auth-title">Crea tu cuenta</h2>
    <p class="auth-sub">Regístrate para hacer pedidos, ver historial y más.</p>

    <form method="POST" action="{{ route('web.register') }}" novalidate>
      @csrf

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="name">Nombre</label>
          <input class="form-input" id="name" name="name" type="text" value="{{ old('name') }}" required />
          @error('name')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label" for="apellido">Apellido</label>
          <input class="form-input" id="apellido" name="apellido" type="text" value="{{ old('apellido') }}" required />
          @error('apellido')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="cedula">Cédula (opcional)</label>
        <input class="form-input" id="cedula" name="cedula" type="text" value="{{ old('cedula') }}" />
        @error('cedula')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="telefono">Teléfono (opcional)</label>
        <input class="form-input" id="telefono" name="telefono" type="text" value="{{ old('telefono') }}" />
        @error('telefono')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Correo electrónico</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" required />
        @error('email')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input class="form-input" id="password" name="password" type="password" required />
          @error('password')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
          <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" required />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="ciudad">Ciudad (opcional)</label>
        <input class="form-input" id="ciudad" name="ciudad" type="text" value="{{ old('ciudad') }}" />
        @error('ciudad')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="direccion">Dirección (opcional)</label>
        <textarea class="form-input" id="direccion" name="direccion">{{ old('direccion') }}</textarea>
        @error('direccion')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <button type="submit" class="form-submit">Crear mi cuenta</button>
      </div>
    </form>

    <div class="auth-switch">¿Ya tienes cuenta? <a href="{{ route('web.login') }}">Inicia sesión</a></div>
  </div>
</div>
@endsection
