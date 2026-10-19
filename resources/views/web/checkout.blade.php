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
            <div class="payment-method" :class="{ 'selected': tipoEntrega === 'delivery', 'disabled': !metodoOnline }" @click.prevent="if (metodoOnline) { tipoEntrega = 'delivery'; if (metodo === 'fisico') metodo = metodoOnline }">
              <div class="payment-method-icon">🚚</div>
              <div class="payment-method-name">Delivery</div>
              <div class="payment-method-sub" x-show="!metodoOnline">Requiere pago en línea: no disponible por ahora</div>
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
          @if($metodos['transferencia'])
          <div class="payment-method" :class="{ 'selected': metodo === 'transferencia' }" @click="metodo = 'transferencia'">
            <div class="payment-method-icon">🏦</div>
            <div class="payment-method-name">Transferencia bancaria</div>
            <div class="payment-method-sub">En bolívares a tasa BCV</div>
          </div>
          @endif

          @if($metodos['pagomovil'])
          <div class="payment-method" :class="{ 'selected': metodo === 'pagomovil' }" @click="metodo = 'pagomovil'">
            <div class="payment-method-icon">📱</div>
            <div class="payment-method-name">Pago móvil</div>
            <div class="payment-method-sub">En bolívares a tasa BCV</div>
          </div>
          @endif

          @if($metodos['binance'])
          <div class="payment-method" :class="{ 'selected': metodo === 'binance' }" @click="metodo = 'binance'">
            <div class="payment-method-icon">💱</div>
            <div class="payment-method-name">Binance</div>
            <div class="payment-method-sub">USDT</div>
          </div>
          @endif

          <div class="payment-method" :class="{ 'selected': metodo === 'fisico', 'disabled': tipoEntrega === 'delivery' }" @click.prevent="if (tipoEntrega !== 'delivery') metodo = 'fisico'">
            <div class="payment-method-icon">🏪</div>
            <div class="payment-method-name">Pago en tienda</div>
            <div class="payment-method-sub" x-text="tipoEntrega === 'delivery' ? 'No disponible con delivery' : 'Efectivo al retirar'"></div>
          </div>
        </div>

        <input type="hidden" name="metodo_pago" :value="metodo">

        @php
          $tr = $datosPago['transferencia']; $pm = $datosPago['pagomovil']; $bn = $datosPago['binance'];
          $bancos = config('pagos.bancos');
          $nombreBanco = fn ($c) => $c ? $c . ' - ' . ($bancos[$c] ?? '') : '';
          $cuenta = trim(chunk_split($tr['numero_cuenta'], 4, ' '));
          $telefono = strlen($pm['telefono']) === 11 ? substr($pm['telefono'], 0, 4) . '-' . substr($pm['telefono'], 4) : $pm['telefono'];
        @endphp
        @if($metodos['transferencia'])
        <div class="payment-detail-box" :class="{ 'active': metodo === 'transferencia' }">
          <div class="pay-data-row"><div class="pay-data-key">Banco</div><div class="pay-data-val">{{ $nombreBanco($tr['banco']) }}</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Cuenta {{ $tr['tipo_cuenta'] === 'ahorro' ? 'de ahorro' : 'corriente' }}</div><div class="pay-data-val">{{ $cuenta }}</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Titular</div><div class="pay-data-val">{{ $tr['titular'] }}</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Cédula / RIF</div><div class="pay-data-val">{{ $tr['documento'] }}</div></div>
          <template x-if="tasa"><div class="pay-data-row"><div class="pay-data-key">Monto a transferir</div><div class="pay-data-val" style="font-weight:700" x-text="'Bs ' + bs(totalBs)"></div></div></template>
        </div>
        @endif

        @if($metodos['pagomovil'])
        <div class="payment-detail-box" :class="{ 'active': metodo === 'pagomovil' }">
          <div class="pay-data-row"><div class="pay-data-key">Teléfono</div><div class="pay-data-val">{{ $telefono }}</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Banco</div><div class="pay-data-val">{{ $nombreBanco($pm['banco']) }}</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Cédula / RIF</div><div class="pay-data-val">{{ $pm['documento'] }}</div></div>
          <template x-if="tasa"><div class="pay-data-row"><div class="pay-data-key">Monto a pagar</div><div class="pay-data-val" style="font-weight:700" x-text="'Bs ' + bs(totalBs)"></div></div></template>
        </div>
        @endif

        @if($metodos['binance'])
        <div class="payment-detail-box" :class="{ 'active': metodo === 'binance' }">
          <div class="pay-data-row"><div class="pay-data-key">Binance Pay ID</div><div class="pay-data-val">{{ $bn['pay_id'] }}</div></div>
          @if($bn['correo'])
          <div class="pay-data-row"><div class="pay-data-key">Correo</div><div class="pay-data-val">{{ $bn['correo'] }}</div></div>
          @endif
          <div class="pay-data-row"><div class="pay-data-key">Moneda</div><div class="pay-data-val">USDT</div></div>
          <div class="pay-data-row"><div class="pay-data-key">Monto a pagar</div><div class="pay-data-val" style="font-weight:700" x-text="total.toFixed(2) + ' USDT'"></div></div>
        </div>
        @endif

        <div class="payment-detail-box" :class="{ 'active': metodo === 'fisico' }">
          <div class="pay-data-row"><div class="pay-data-key">Nota</div><div class="pay-data-val">Paga en efectivo (USD o Bs) al retirar en tienda física</div></div>
        </div>

        <div class="form-group" x-show="metodo !== 'fisico'">
          <label class="form-label">Comprobante de pago (imagen o PDF)</label>
          <input id="comprobante" x-ref="file" type="file" name="comprobante" accept="image/png,image/jpeg,application/pdf" @change="onFileChange($event)" style="display:none">
          <label class="upload-area" for="comprobante" style="display:block;cursor:pointer"
            @dragover.prevent=""
            @drop.prevent="onDrop($event)">
            <div class="upload-icon" x-text="fileName ? '✅' : '📎'"></div>
            <div class="upload-text" x-text="fileName ? fileName : 'Arrastra o haz click para subir el comprobante'"></div>
            <div class="upload-hint" x-text="fileHint ? fileHint : 'PNG, JPG o PDF - Máx 5MB'"></div>
          </label>
          @error('comprobante')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          <div class="security-note">El archivo se almacena de forma segura y solo el personal autorizado puede verificarlo.</div>
        </div>

        <input type="hidden" name="moneda_pagada" :value="esBs ? 'VES' : (metodo === 'binance' ? 'USDT' : '')">

        <div x-show="metodo !== 'fisico'">
          <div class="security-note" style="margin-bottom:.8rem" x-show="esBs">
            Los datos de abajo se leen solos del comprobante. Escríbelos solo si quieres confirmarlos o si subiste un PDF.
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="monto_pagado" x-text="esBs ? 'Monto pagado (Bs)' : 'Monto pagado (USDT)'"></label>
              <input class="form-input" id="monto_pagado" type="number" step="0.01" min="0" name="monto_pagado" value="{{ old('monto_pagado') }}" :placeholder="esBs ? (tasa ? bs(totalBs) : '') : total.toFixed(2)">
              @error('monto_pagado')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label class="form-label" for="fecha_pago">Fecha del pago</label>
              <input class="form-input" id="fecha_pago" type="date" name="fecha_pago" value="{{ old('fecha_pago') }}" max="{{ now()->toDateString() }}">
              @error('fecha_pago')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="numero_referencia" x-text="esBs ? 'Número de operación' : 'ID de transacción'"></label>
            <input class="form-input" id="numero_referencia" type="text" name="numero_referencia" inputmode="numeric" :placeholder="esBs ? 'Ej. 007693346569 (o sus últimos dígitos)' : 'Ej. 284910482'" value="{{ old('numero_referencia') }}">
            @error('numero_referencia')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
          </div>

          <div class="form-row" x-show="esBs">
            <div class="form-group">
              <label class="form-label" for="banco_origen">Banco desde el que pagaste</label>
              <select class="form-input" id="banco_origen" name="banco_origen">
                <option value="">Seleccionar…</option>
                @foreach(config('pagos.bancos') as $codigo => $nombre)
                  <option value="{{ $codigo }} - {{ $nombre }}" @selected(old('banco_origen') === "$codigo - $nombre")>{{ $codigo }} - {{ $nombre }}</option>
                @endforeach
              </select>
              @error('banco_origen')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
            <div class="form-group" x-show="metodo === 'pagomovil'">
              <label class="form-label" for="cedula_pagador">Cédula del pagador</label>
              <input class="form-input" id="cedula_pagador" type="text" name="cedula_pagador" placeholder="Ej. V12345678" value="{{ old('cedula_pagador') }}">
              @error('cedula_pagador')<div style="color:#EF4444;font-size:.8rem;margin-top:.3rem">{{ $message }}</div>@enderror
            </div>
          </div>
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
        <template x-if="tasa">
          <div class="order-summary-item" style="font-weight:600">
            <span>Total en bolívares</span>
            <span x-text="'Bs ' + bs(totalBs)"></span>
          </div>
        </template>
        <div style="color:var(--c-muted);font-size:.78rem;margin-top:.3rem">
          @if($tasaBcv)
            Tasa oficial BCV: Bs {{ number_format($tasaBcv, 2, ',', '.') }} por USD{{ $fechaTasa ? ' · vigente desde el ' . $fechaTasa->format('d/m/Y') : '' }}
          @else
            Tasa BCV no disponible: el monto en bolívares se confirmará al verificar el pago.
          @endif
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
    // Primer método online configurado por la tienda (null si solo hay efectivo)
    metodoOnline: @json(collect(['transferencia', 'pagomovil', 'binance'])->first(fn ($m) => $metodos[$m])),
    metodo: @json(collect(['transferencia', 'pagomovil', 'binance'])->first(fn ($m) => $metodos[$m]) ?? 'fisico'),
    tasa: {{ $tasaBcv ? (float) $tasaBcv : 'null' }},
    tipoEntrega: 'retiro',
    fileName: '',
    fileHint: '',
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
    get totalBs() { return this.tasa ? Math.round(this.total * this.tasa * 100) / 100 : null; },
    get esBs() { return this.metodo === 'transferencia' || this.metodo === 'pagomovil'; },
    bs(n) { return n === null ? '' : Number(n).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    onFileChange(e) {
      const f = e.target.files && e.target.files[0];
      if (f) {
        this.fileName = f.name;
        this.fileHint = (f.size / 1024).toFixed(0) + ' KB · ' + (f.type || 'archivo');
      } else {
        this.fileName = '';
        this.fileHint = '';
      }
    },
    onDrop(e) {
      const dt = e.dataTransfer;
      if (dt && dt.files && dt.files.length > 0) {
        this.$refs.file.files = dt.files;
        const f = dt.files[0];
        this.fileName = f.name;
        this.fileHint = (f.size / 1024).toFixed(0) + ' KB · ' + (f.type || 'archivo');
      }
    }
  }
}
</script>
@endpush

@endsection
