@extends('layouts.web')

@section('title', 'Finalizar pedido')

@section('content')
<style>
.payment-method.disabled { opacity: .45; cursor: not-allowed; }
</style>
<div x-data="checkoutApp()" class="section active">
  <div class="breadcrumb">
    <a href="{{ route('web.home') }}">Inicio</a>
    <span class="sep">›</span>
    <span>Finalizar pedido</span>
  </div>

  <div class="section-title">Finalizar pedido</div>

  <form action="{{ route('web.checkout.store') }}" method="POST" enctype="multipart/form-data" class="checkout-grid">
    @csrf

    <input type="hidden" name="items" :value="JSON.stringify(carrito)">

    <div>
      <div class="panel">
        <div class="checkout-section-title">📋 Datos de entrega</div>

        <div class="form-group">
          <label class="form-label">Tipo de entrega</label>
          <div class="payment-methods" role="tablist" style="grid-template-columns:1fr 1fr">
            <div class="payment-method" :class="{ 'selected': tipoEntrega === 'retiro' }" @click.prevent="tipoEntrega = 'retiro'; $refs.direccion.value = ''; $refs.ciudad.value = ''">
              <div class="payment-method-icon">🏪</div>
              <div class="payment-method-name">Retiro en tienda</div>
            </div>
            <div class="payment-method" :class="{ 'selected': tipoEntrega === 'delivery' }" @click.prevent="tipoEntrega = 'delivery'; if (metodo === 'fisico') metodo = 'transferencia'">
              <div class="payment-method-icon">🚚</div>
              <div class="payment-method-name">Delivery</div>
            </div>
          </div>
          <input type="hidden" name="tipo_entrega" :value="tipoEntrega">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="entrega_nombre">Nombre</label>
            <input class="form-input" id="entrega_nombre" type="text" name="entrega_nombre" value="{{ old('entrega_nombre', auth()->user()?->name) }}" required />
            @error('entrega_nombre')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="entrega_apellido">Apellido</label>
            <input class="form-input" id="entrega_apellido" type="text" name="entrega_apellido" value="{{ old('entrega_apellido', auth()->user()?->apellido) }}" required />
            @error('entrega_apellido')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="entrega_telefono">Teléfono / WhatsApp</label>
          <input class="form-input" id="entrega_telefono" type="text" name="entrega_telefono" value="{{ old('entrega_telefono', auth()->user()?->telefono) }}" placeholder="0414-1234567" required />
          @error('entrega_telefono')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group" x-show="tipoEntrega === 'delivery'">
          <label class="form-label" for="entrega_direccion">Dirección</label>
          <input class="form-input" id="entrega_direccion" x-ref="direccion" type="text" name="entrega_direccion" value="{{ old('entrega_direccion', auth()->user()?->direccion) }}" :required="tipoEntrega === 'delivery'" />
          @error('entrega_direccion')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group" x-show="tipoEntrega === 'delivery'">
          <label class="form-label" for="entrega_ciudad">Ciudad</label>
          <input class="form-input" id="entrega_ciudad" x-ref="ciudad" type="text" name="entrega_ciudad" value="{{ old('entrega_ciudad', auth()->user()?->ciudad ?? 'Valera') }}" :required="tipoEntrega === 'delivery'" />
          @error('entrega_ciudad')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="notas">Notas adicionales (opcional)</label>
          <textarea class="form-input" id="notas" name="notas" placeholder="Instrucciones para la entrega o retiro">{{ old('notas') }}</textarea>
        </div>
      </div>

      <div class="panel">
        <div class="checkout-section-title">💳 Método de pago</div>

        <div class="payment-methods" role="tablist">
          <div class="payment-method" :class="{ 'selected': metodo === 'transferencia' }" @click="metodo = 'transferencia'">
            <div class="payment-method-icon">🏦</div>
            <div class="payment-method-name">Transferencia bancaria</div>
            <div class="payment-method-sub">Datos bancarios y pago móvil</div>
          </div>

          <div class="payment-method" :class="{ 'selected': metodo === 'binance' }" @click="metodo = 'binance'">
            <div class="payment-method-icon">💱</div>
            <div class="payment-method-name">Binance</div>
            <div class="payment-method-sub">ID y moneda</div>
          </div>

          <div class="payment-method" :class="{ 'selected': metodo === 'fisico', 'disabled': tipoEntrega === 'delivery' }" @click.prevent="if (tipoEntrega !== 'delivery') metodo = 'fisico'">
            <div class="payment-method-icon">🏪</div>
            <div class="payment-method-name">Pago en tienda</div>
            <div class="payment-method-sub" x-text="tipoEntrega === 'delivery' ? 'No disponible con delivery' : 'Efectivo al retirar'"></div>
          </div>
        </div>

        <input type="hidden" name="metodo_pago" :value="metodo">

        <div class="payment-detail-box" :class="{ 'active': metodo === 'transferencia' }">
          <div class="pay-data-row"><div class="pay-data-key">Banco</div><div class="pay-data-val">Banco Provincial / Banesco</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Tipo</div><div class="pay-data-val">Cuenta Corriente</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Titular</div><div class="pay-data-val">Smartphone World C.A.</div></div>
          <div class="pay-data-row"><div class="pay-data-key">RIF</div><div class="pay-data-val">J-50123456-7</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Pago móvil</div><div class="pay-data-val">0414-1234567 · Banesco / Provincial</div></div>
        </div>

        <div class="payment-detail-box" :class="{ 'active': metodo === 'binance' }">
          <div class="pay-data-row"><div class="pay-data-key">Binance Pay ID</div><div class="pay-data-val">284910482</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Moneda</div><div class="pay-data-val">USDT</div></div>
        </div>

        <div class="payment-detail-box" :class="{ 'active': metodo === 'fisico' }">
          <div class="pay-data-row"><div class="pay-data-key">Nota</div><div class="pay-data-val">Paga en efectivo (USD o Bs) al retirar en tienda física</div></div>
        </div>

        <div class="form-group" x-show="metodo !== 'fisico'">
          <label class="form-label">Comprobante de pago (imagen o PDF)</label>
          <div class="upload-area" @click.prevent="$refs.file.click()">
            <input x-ref="file" type="file" name="comprobante" accept="image/png,image/jpeg,application/pdf" @change="onFileChange($event)" style="display:none">
            <div class="upload-icon">📎</div>
            <div class="upload-text" x-text="fileName ? fileName : 'Arrastra o haz click para subir el comprobante'"></div>
            <div class="upload-hint">PNG, JPG o PDF - Máx 5MB</div>
          </div>
          @error('comprobante')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          <div class="security-note">El archivo se almacena de forma segura y solo el personal autorizado puede verificarlo.</div>
        </div>

        <div class="form-group" x-show="metodo !== 'fisico'">
          <label class="form-label" for="numero_referencia">Número de referencia bancaria o ID de transacción</label>
          <input class="form-input" id="numero_referencia" type="text" name="numero_referencia" placeholder="Ej. 12345678" value="{{ old('numero_referencia') }}">
          @error('numero_referencia')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <button type="submit" class="form-submit">✅ Confirmar y enviar pedido</button>
        </div>
      </div>
    </div>

    <div>
      <div class="panel">
        <div class="checkout-section-title">🛒 Resumen del pedido</div>
        <template x-for="item in carrito" :key="item.id">
          <div class="order-summary-item">
            <span x-text="(item.emoji ?? '📦') + ' ' + item.nombre + ' × ' + item.cantidad"></span>
            <span x-text="'$' + (item.precio * item.cantidad).toFixed(2)"></span>
          </div>
        </template>

        <div class="order-total-row">
          <span>Total</span>
          <span x-text="'$' + total.toFixed(2)"></span>
        </div>

        <div class="panel" style="margin-top:1rem;background:var(--c-input)">
          <p style="color:var(--c-muted);font-size:.88rem;line-height:1.4">
            Al confirmar tu pedido, el stock quedará reservado y el equipo de Smartphone World verificará los datos para el despacho o retiro.
          </p>
        </div>
      </div>
    </div>
  </form>
</div>

@push('scripts')
<script>
function checkoutApp() {
  return {
    carrito: JSON.parse(localStorage.getItem('sw_carrito') || '[]'),
    metodo: 'transferencia',
    tipoEntrega: 'retiro',
    fileName: '',
    reserving: false,
    reserveResult: null,
    async init() {
      // Al entrar al checkout, sincronizar carrito y pedir reserva temporal de stock al servidor
      if (!this.carrito || this.carrito.length === 0) return;
      this.reserving = true;
      try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        const res = await fetch('{{ route('web.checkout.reserve') }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
          body: JSON.stringify({ items: this.carrito })
        });
        const json = await res.json();
        this.reserveResult = json;
        if (!res.ok) {
          console.warn('Reserva de stock:', json.message);
        }
      } catch (e) {
        console.error(e);
      } finally {
        this.reserving = false;
      }
    },
    get total() { return this.carrito.reduce((s,i) => s + i.precio * i.cantidad, 0); },
    onFileChange(e) {
      const f = e.target.files && e.target.files[0];
      if (f) this.fileName = f.name;
      else this.fileName = '';
    }
  }
}
</script>
@endpush

@endsection
