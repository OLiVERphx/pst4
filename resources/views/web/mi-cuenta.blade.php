@extends('layouts.web')

@section('title', 'Mi cuenta')

@section('content')
<style>
.account-grid {
  display: grid;
  grid-template-columns: 420px 1fr;
  gap: 2rem;
  align-items: start;
}
@media (max-width: 960px) {
  .account-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="section active">
  <div class="breadcrumb">
    <a href="{{ route('web.home') }}">Inicio</a>
    <span class="sep">›</span>
    <span>Mi cuenta</span>
  </div>

  <div class="section-title">Mi cuenta</div>

  @if(session('success'))
    <div style="background:rgba(5,150,105,.12);border:1px solid rgba(5,150,105,.3);color:#34D399;padding:.85rem 1.2rem;border-radius:10px;margin-bottom:1.5rem;font-weight:600;display:flex;align-items:center;gap:.6rem">
      <span>✓</span>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#F87171;padding:.85rem 1.2rem;border-radius:10px;margin-bottom:1.5rem;font-size:.9rem">
      <strong>Por favor corrige los siguientes errores:</strong>
      <ul style="margin: .5rem 0 0 1.2rem">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="account-grid">
    <!-- Apartado: Datos del usuario -->
    <div>
      <div class="panel">
        <div class="checkout-section-title">
          <span>👤 Mis datos personales</span>
        </div>
        <p style="color:var(--c-muted);font-size:.85rem;margin-bottom:1.2rem">
          Consulta y actualiza tu información de contacto y dirección de entrega.
        </p>

        <form action="{{ route('web.account.update') }}" method="POST">
          @csrf
          @method('PUT')

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="name">Nombre</label>
              <input class="form-input" id="name" type="text" name="name" value="{{ old('name', $usuario->name) }}" required>
              @error('name')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
              <label class="form-label" for="apellido">Apellido</label>
              <input class="form-input" id="apellido" type="text" name="apellido" value="{{ old('apellido', $usuario->apellido) }}" required>
              @error('apellido')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-input" id="email" type="email" name="email" value="{{ old('email', $usuario->email) }}" required>
            @error('email')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="cedula">Cédula / Documento</label>
              <input class="form-input" id="cedula" type="text" name="cedula" value="{{ old('cedula', $usuario->cedula) }}" placeholder="V-12345678">
              @error('cedula')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
              <label class="form-label" for="telefono">Teléfono / WhatsApp</label>
              <input class="form-input" id="telefono" type="text" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" placeholder="0414-1234567">
              @error('telefono')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="ciudad">Ciudad</label>
            <input class="form-input" id="ciudad" type="text" name="ciudad" value="{{ old('ciudad', $usuario->ciudad ?? 'Valera') }}">
            @error('ciudad')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="direccion">Dirección de entrega</label>
            <textarea class="form-input" id="direccion" name="direccion" rows="2" placeholder="Av. principal, edificio, casa o apto">{{ old('direccion', $usuario->direccion) }}</textarea>
            @error('direccion')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>

          <div style="margin-top:1.2rem;padding-top:1.2rem;border-top:1px solid var(--c-border)">
            <div style="font-size:.82rem;font-weight:700;color:var(--c-muted);margin-bottom:.8rem;text-transform:uppercase;letter-spacing:.04em">
              Cambiar contraseña <span style="font-weight:normal;text-transform:none">(opcional)</span>
            </div>

            <div class="form-group">
              <label class="form-label" for="password">Nueva contraseña</label>
              <input class="form-input" id="password" type="password" name="password" placeholder="Dejar en blanco para no cambiarla">
              @error('password')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
              <label class="form-label" for="password_confirmation">Confirmar nueva contraseña</label>
              <input class="form-input" id="password_confirmation" type="password" name="password_confirmation" placeholder="Repite la nueva contraseña">
            </div>
          </div>

          <button type="submit" class="form-submit" style="margin-top:1rem">
            Guardar cambios
          </button>
        </form>
      </div>
    </div>

    <!-- Apartado: Historial de pedidos -->
    <div>
      <div class="checkout-section-title" style="margin-bottom:1.2rem">
        <span>📦 Historial de pedidos</span>
        <span style="font-size:.85rem;color:var(--c-muted);font-weight:normal">
          ({{ $pedidos->total() }} {{ $pedidos->total() === 1 ? 'pedido' : 'pedidos' }})
        </span>
      </div>

      @if($pedidos->isEmpty())
        <div class="panel">
          <p style="color:var(--c-muted)">Todavía no tienes pedidos. <a href="{{ route('web.catalog') }}" style="color:var(--c-teal);text-decoration:underline">Explorar catálogo</a></p>
        </div>
      @else
        @foreach($pedidos as $pedido)
          <div class="panel" style="margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
              <div>
                <strong>{{ $pedido->numero_pedido }}</strong>
                <span style="color:var(--c-muted);font-size:.85rem"> — {{ $pedido->created_at->format('d/m/Y H:i') }}</span>
              </div>
              <div>
                <span class="chip chip-blue" style="font-size:.8rem">
                  {{ $estados[$pedido->estado] ?? ucfirst(str_replace('_',' ',$pedido->estado)) }}
                </span>
              </div>
            </div>

            <div style="color:var(--c-muted);font-size:.9rem;margin-top:.4rem">
              {{ $pedido->tipo_entrega === 'delivery' ? '🚚 Delivery' : '🏪 Retiro en tienda' }}
              @if($pedido->payment)
                — Pago: {{ ucfirst($pedido->payment->metodo) }}
              @endif
              @if($pedido->tipo_entrega === 'delivery' && $pedido->fecha_estimada_entrega)
                — Llega: {{ \Carbon\Carbon::parse($pedido->fecha_estimada_entrega)->format('d/m/Y H:i') }}
              @endif
            </div>

            <div style="margin-top:.6rem">
              @foreach($pedido->items as $item)
                <div class="order-summary-item">
                  <span>{{ $item->product->nombre ?? 'Producto eliminado' }} × {{ $item->cantidad }}</span>
                  <span>${{ number_format($item->subtotal, 2) }}</span>
                </div>
              @endforeach
            </div>

            <div class="order-total-row">
              <span>Total</span>
              <span>${{ number_format($pedido->total, 2) }}</span>
            </div>
          </div>
        @endforeach

        <div style="margin-top:1rem">
          {{ $pedidos->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
