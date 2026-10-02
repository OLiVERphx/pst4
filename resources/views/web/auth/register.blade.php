@extends('layouts.web')

@section('title', 'Crear cuenta')

@section('content')
<div class="auth-page">
  <div class="auth-card" style="max-width: 460px;" x-data="registerWizard()">
    <div class="auth-logo">
      <span class="brand-icon">SW</span>
      <div>
        <div style="font-weight:800;font-size:1.1rem">Smartphone World</div>
        <div style="font-size:.78rem;color:var(--c-muted)">Registro de clientes</div>
      </div>
    </div>

    <!-- Indicador de pasos -->
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem">
      <div style="flex:1;display:flex;align-items:center;gap:.5rem">
        <div style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;transition:all .2s;"
             :style="paso >= 1 ? 'background:var(--c-blue);color:#fff;' : 'background:var(--c-border);color:var(--c-muted);'">1</div>
        <span style="font-size:.82rem;font-weight:600;" :style="paso === 1 ? 'color:var(--c-text);' : 'color:var(--c-muted);'">Cuenta</span>
      </div>
      <div style="flex:1;height:2px;transition:all .2s;" :style="paso >= 2 ? 'background:var(--c-blue);' : 'background:var(--c-border);'"></div>
      <div style="flex:1;display:flex;align-items:center;gap:.5rem;justify-content:flex-end">
        <div style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;transition:all .2s;"
             :style="paso >= 2 ? 'background:var(--c-blue);color:#fff;' : 'background:var(--c-border);color:var(--c-muted);'">2</div>
        <span style="font-size:.82rem;font-weight:600;" :style="paso === 2 ? 'color:var(--c-text);' : 'color:var(--c-muted);'">Despacho</span>
      </div>
    </div>

    <form method="POST" action="{{ route('web.register') }}" novalidate id="registerForm">
      @csrf

      <!-- PASO 1: DATOS DE CUENTA -->
      <div x-show="paso === 1" x-transition.opacity>
        <h2 class="auth-title">Crea tu cuenta</h2>
        <p class="auth-sub" style="margin-bottom: 1.25rem;">Ingresa tus datos básicos para registrarte en la tienda.</p>

        <template x-if="errorPaso1">
          <div style="color:#EF4444;font-size:.84rem;margin-bottom:1rem;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:8px;padding:.6rem .8rem;" x-text="errorPaso1"></div>
        </template>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="name">Nombre</label>
            <input class="form-input" id="name" name="name" type="text" placeholder="Juan" x-model="name" required />
            @error('name')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="apellido">Apellido</label>
            <input class="form-input" id="apellido" name="apellido" type="text" placeholder="Pérez" x-model="apellido" required />
            @error('apellido')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="email">Correo electrónico</label>
          <input class="form-input" id="email" name="email" type="email" placeholder="tu@correo.com" x-model="email" required />
          @error('email')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="password">Contraseña</label>
            <input class="form-input" id="password" name="password" type="password" placeholder="Mínimo 8 caracteres" x-model="password" required />
            @error('password')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="password_confirmation">Confirmar</label>
            <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite contraseña" x-model="password_confirmation" required />
          </div>
        </div>

        <div class="form-group" style="margin-top:1.5rem">
          <button type="button" class="form-submit" @click="irAPaso2()" style="display:flex;align-items:center;justify-content:center;gap:.5rem">
            <span>Continuar a datos de entrega</span>
            <span>→</span>
          </button>
        </div>

        <div class="auth-switch">¿Ya tienes cuenta? <a href="{{ route('web.login') }}">Inicia sesión</a></div>
      </div>

      <!-- PASO 2: DATOS DE FACTURACIÓN Y DESPACHO -->
      <div x-show="paso === 2" x-transition.opacity x-cloak>
        <h2 class="auth-title">Datos para pedidos</h2>
        
        <!-- Indicación formal para el negocio -->
        <div style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);border-radius:10px;padding:.85rem 1rem;margin-bottom:1.25rem;font-size:.83rem;line-height:1.45;color:var(--c-text);">
          <div style="font-weight:700;display:flex;align-items:center;gap:.4rem;margin-bottom:.25rem;color:var(--c-blue);">
            <span>🛡️</span> Requerido para procesar pedidos
          </div>
          Para procesar tus compras y garantizar un despacho seguro conforme a nuestras políticas comerciales, es necesario completar tus datos de contacto y entrega.
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="cedula">Cédula / RIF</label>
            <input class="form-input" id="cedula" name="cedula" type="text" placeholder="V-12345678" value="{{ old('cedula') }}" />
            @error('cedula')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>
          <div class="form-group">
            <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="telefono">Teléfono / WhatsApp</label>
            <input class="form-input" id="telefono" name="telefono" type="text" placeholder="0414-1234567" value="{{ old('telefono') }}" />
            @error('telefono')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="ciudad">Ciudad / Municipio</label>
          <input class="form-input" id="ciudad" name="ciudad" type="text" placeholder="Valera" value="{{ old('ciudad') }}" />
          @error('ciudad')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" style="text-transform:none;letter-spacing:normal;" for="direccion">Dirección de entrega</label>
          <input class="form-input" id="direccion" name="direccion" type="text" placeholder="Sector, calle, casa / apto, punto de referencia" value="{{ old('direccion') }}" />
          @error('direccion')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group" style="margin-top:1.5rem">
          <button type="submit" class="form-submit" style="display:flex;align-items:center;justify-content:center;gap:.5rem">
            <span>Crear mi cuenta y guardar datos</span>
            <span>✓</span>
          </button>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1.2rem;font-size:.85rem">
          <button type="button" @click="paso = 1" style="background:none;border:none;color:var(--c-muted);cursor:pointer;display:flex;align-items:center;gap:.3rem;padding:0;">
            ← Volver al paso 1
          </button>
          <button type="submit" style="background:none;border:none;color:var(--c-muted);cursor:pointer;text-decoration:underline;padding:0;" title="Podrás ingresar estos datos antes de finalizar tu primera compra">
            Completar más tarde
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function registerWizard() {
  return {
    paso: {{ ($errors->has('cedula') || $errors->has('telefono') || $errors->has('ciudad') || $errors->has('direccion')) ? 2 : 1 }},
    name: '{{ old('name') }}',
    apellido: '{{ old('apellido') }}',
    email: '{{ old('email') }}',
    password: '',
    password_confirmation: '',
    errorPaso1: '',
    irAPaso2() {
      if (!this.name.trim() || !this.apellido.trim()) {
        this.errorPaso1 = 'Por favor ingresa tu nombre y apellido.';
        return;
      }
      if (!this.email.trim() || !this.email.includes('@')) {
        this.errorPaso1 = 'Por favor ingresa un correo electrónico válido.';
        return;
      }
      if (!this.password || this.password.length < 8) {
        this.errorPaso1 = 'La contraseña debe tener al menos 8 caracteres.';
        return;
      }
      if (this.password !== this.password_confirmation) {
        this.errorPaso1 = 'Las contraseñas no coinciden.';
        return;
      }
      this.errorPaso1 = '';
      this.paso = 2;
    }
  }
}
</script>
@endsection
