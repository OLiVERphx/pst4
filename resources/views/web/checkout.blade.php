@extends('layouts.web')

@section('title', 'Finalizar pedido')

@section('content')
<div x-data="checkoutApp()" class="section active">
  <div class="breadcrumb">
    <a href="{{ route('web.home') }}">Inicio</a>
    <span class="sep">›</span>
    <span>Finalizar pedido</span>
  </div>

  <div class="section-title">Finalizar pedido</div>

  <form action="{{ route('web.checkout.store') }}" method="POST" enctype="multipart/form-data" class="checkout-grid" x-data="checkoutApp()">
    @csrf

    <input type="hidden" name="items" :value="JSON.stringify(carrito)">

    <div>
      <div class="panel">
        <div class="checkout-section-title">📋 Datos de entrega</div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="entrega_nombre">Nombre</label>
            <input class="form-input" id="entrega_nombre" type="text" name="entrega_nombre" required />
            @error('entrega_nombre')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="entrega_apellido">Apellido</label>
            <input class="form-input" id="entrega_apellido" type="text" name="entrega_apellido" required />
            @error('entrega_apellido')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="entrega_telefono">Teléfono</label>
          <input class="form-input" id="entrega_telefono" type="text" name="entrega_telefono" required />
          @error('entrega_telefono')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="entrega_direccion">Dirección</label>
          <input class="form-input" id="entrega_direccion" type="text" name="entrega_direccion" required />
          @error('entrega_direccion')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="entrega_ciudad">Ciudad</label>
          <input class="form-input" id="entrega_ciudad" type="text" name="entrega_ciudad" required />
          @error('entrega_ciudad')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="notas">Notas (opcional)</label>
          <textarea class="form-input" id="notas" name="notas"></textarea>
        </div>
      </div>

      <div class="panel" x-data>
        <div class="checkout-section-title">💳 Método de pago</div>

        <div class="payment-methods" role="tablist">
          <div class="payment-method" :class="{ 'selected': metodo === 'transferencia' }" @click.prevent="metodo = 'transferencia'">
            <div class="payment-method-icon">🏦</div>
            <div class="payment-method-name">Transferencia bancaria</div>
            <div class="payment-method-sub">Datos bancarios y pago móvil</div>
          </div>

          <div class="payment-method" :class="{ 'selected': metodo === 'binance' }" @click.prevent="metodo = 'binance'">
            <div class="payment-method-icon">💱</div>
            <div class="payment-method-name">Binance</div>
            <div class="payment-method-sub">ID y moneda</div>
          </div>

          <div class="payment-method" :class="{ 'selected': metodo === 'fisico' }" @click.prevent="metodo = 'fisico'">
            <div class="payment-method-icon">🏪</div>
            <div class="payment-method-name">Pago en tienda</div>
            <div class="payment-method-sub">Efectivo al retirar</div>
          </div>
        </div>

        <input type="hidden" name="metodo_pago" :value="metodo">

        <div class="payment-detail-box" :class="{ 'active': metodo === 'transferencia' }">
          <div class="pay-data-row"><div class="pay-data-key">Banco</div><div class="pay-data-val">Banco Ejemplo</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Tipo</div><div class="pay-data-val">Corriente</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Titular</div><div class="pay-data-val">SmartPhone World</div></div>
          <div class="pay-data-row"><div class="pay-data-key">RIF</div><div class="pay-data-val">J-12345678-9</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Pago móvil</div><div class="pay-data-val">04141234567</div></div>
        </div>

        <div class="payment-detail-box" :class="{ 'active': metodo === 'binance' }">
          <div class="pay-data-row"><div class="pay-data-key">ID</div><div class="pay-data-val">BINANCE-123456</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Moneda</div><div class="pay-data-val">USDT</div></div>
        </div>

        <div class="payment-detail-box" :class="{ 'active': metodo === 'fisico' }">
          <div class="pay-data-row"><div class="pay-data-key">Nota</div><div class="pay-data-val">Paga en efectivo al retirar en tienda o al recibir</div></div>
        </div>

        <div class="form-group">
          <label class="form-label">Comprobante (imagen o PDF)</label>
          <div class="upload-area" @click.prevent="$refs.file.click()">
            <input x-ref="file" type="file" name="comprobante" accept="image/png,image/jpeg,application/pdf" @change="onFileChange($event)" style="display:none">
            <div class="upload-icon">📎</div>
            <div class="upload-text" x-text="fileName ? fileName : 'Arrastra o haz click para subir el comprobante'"></div>
            <div class="upload-hint">PNG, JPG o PDF - Máx 5MB</div>
          </div>
          @error('comprobante')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          <div class="security-note">El archivo puede ser usado para verificar tu pago. Máx 5MB.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="numero_referencia">Número de referencia (opcional)</label>
          <input class="form-input" id="numero_referencia" type="text" name="numero_referencia">
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
            <span x-text="item.emoji + ' ' + item.nombre + ' × ' + item.cantidad"></span>
            <span x-text="'$' + (item.precio * item.cantidad).toFixed(2)"></span>
          </div>
        </template>

        <div class="order-total-row">
          <span>Total</span>
          <span x-text="'$' + total.toFixed(2)"></span>
        </div>

        <div class="panel">
          <p style="color:var(--c-muted);font-size:.95rem">Al confirmar el pedido, recibirás instrucciones para el pago (si aplica) y el equipo verificará tu comprobante.</p>
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
    fileName: '',
    reserving: false,
    reserveResult: null,
    async init() {
      // Al entrar al checkout, pedir reserva temporal de stock al servidor
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
          alert(json.message || 'No fue posible reservar todo el stock. Por favor revise su carrito.');
        }
      } catch (e) {
        console.error(e);
        alert('Ocurrió un error al intentar reservar el stock. Intente de nuevo.');
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
