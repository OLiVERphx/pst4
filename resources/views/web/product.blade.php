@extends('layouts.web')

@section('title', $product->nombre ?? 'Producto')

@section('content')
<div class="section active">
  <div class="breadcrumb">
    <a href="{{ route('web.home') }}">Inicio</a>
    <span class="sep">›</span>
    <a href="{{ route('web.catalog') }}">Catálogo</a>
    <span class="sep">›</span>
    <span>{{ $product->nombre }}</span>
  </div>

  <div class="product-page">
    <div class="product-main">
      <div class="modal-img">
        <span style="font-size:6rem">{{ $emojis[$product->category?->nombre] ?? '📦' }}</span>
      </div>

      <div class="modal-body" x-data="{
        cantidad: 1,
        tipo: 'detal'
      }">
        <div class="modal-brand">{{ $product->brand?->nombre }}</div>
        <div class="modal-name">{{ $product->nombre }}</div>
        <div class="modal-cat">{{ ($product->category?->nombre ?? '') . ' · Código: ' . ($product->codigo ?? '') }}</div>

        <div class="modal-price-row">
          <div class="modal-price">${{ number_format($product->precio_detal, 2) }}</div>
          <div class="modal-price-mayor">Mayor: ${{ number_format($product->precio_mayor, 2) }}</div>
        </div>

        <div class="modal-stock">
          <div class="stock-dot" style="background: {{ $product->stock > $product->stock_minimo ? 'var(--c-green)' : ($product->stock > 0 ? 'var(--c-orange)' : '#EF4444') }}"></div>
          <span>
            @if($product->stock > $product->stock_minimo)
              {{ $product->stock }} unidades disponibles
            @elseif($product->stock > 0)
              Solo {{ $product->stock }} unidades
            @else
              Sin stock
            @endif
          </span>
        </div>

        <div class="modal-qty-row">
          <span class="qty-label">Cantidad:</span>
          <div class="qty-ctrl">
            <button type="button" class="qty-btn" @click="cantidad = Math.max(1, cantidad - 1)">−</button>
            <span class="qty-val" x-text="cantidad">1</span>
            <button type="button" class="qty-btn" @click="cantidad++">+</button>
          </div>
        </div>

        <div class="modal-type-row">
          <button type="button" class="type-btn" :class="{ active: tipo==='detal' }" @click="tipo='detal'">🛍️ Al detal</button>
          <button type="button" class="type-btn" :class="{ active: tipo==='mayor' }" @click="tipo='mayor'">📦 Al mayor</button>
        </div>

        <button class="modal-add-btn" type="button" @click="$dispatch('agregar-carrito', { producto: {{ json_encode([
          'id'=>$product->id,
          'nombre'=>$product->nombre,
          'precio_detal'=>$product->precio_detal,
          'precio_mayor'=>$product->precio_mayor,
          'emoji'=>$emojis[$product->category?->nombre] ?? '📦',
          'slug'=>$product->slug
        ]) }}, cantidad: cantidad, tipo: tipo })">
          Agregar al carrito
        </button>

        <div class="modal-specs">
          <div class="modal-specs-title">Especificaciones</div>
          <div class="spec-row"><span class="spec-key">Código</span><span class="spec-val">{{ $product->codigo }}</span></div>
          <div class="spec-row"><span class="spec-key">Marca</span><span class="spec-val">{{ $product->brand?->nombre }}</span></div>
          <div class="spec-row"><span class="spec-key">Precio detal</span><span class="spec-val">${{ number_format($product->precio_detal, 2) }}</span></div>
          <div class="spec-row"><span class="spec-key">Precio mayor</span><span class="spec-val">${{ number_format($product->precio_mayor, 2) }}</span></div>
          <div class="spec-row"><span class="spec-key">Stock</span><span class="spec-val">{{ $product->stock }} uds</span></div>
        </div>
      </div>
    </div>

    <div class="related-products">
      <h3 class="section-title">Productos relacionados</h3>
      <div class="product-grid">
        @foreach($relacionados as $p)
          <div class="product-card">
            <a href="{{ route('web.product', $p->slug ?? $p->id) }}" class="product-card-img">
              <span style="font-size:4rem">{{ $emojis[$p->category?->nombre] ?? '📦' }}</span>
            </a>
            <div class="product-card-body">
              <div class="product-name">{{ $p->nombre }}</div>
              <div class="product-brand">{{ $p->brand?->nombre ?? '' }} · {{ $p->category?->nombre ?? '' }}</div>
              <div class="product-bottom">
                <div class="product-price">${{ number_format($p->precio_detal, 2) }}</div>
                <button class="product-add-btn"
                  @click="$dispatch('agregar-carrito', { producto: {{ json_encode([
                    'id'=>$p->id,'nombre'=>$p->nombre,'precio_detal'=>$p->precio_detal,'precio_mayor'=>$p->precio_mayor,'emoji'=>$emojis[$p->category?->nombre] ?? '📦','slug'=>$p->slug
                  ]) }}, cantidad: 1, tipo: 'detal' })">
                  +
                </button>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
// No extra JS needed; Alpine used inline in modal-body x-data
</script>
@endpush

@endsection