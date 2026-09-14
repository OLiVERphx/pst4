@extends('layouts.web')

@section('content')

@php
$emojis = ['Audífonos'=>'🎧','Fundas'=>'📱','Cargadores'=>'🔌','Cables'=>'🔗','Protectores'=>'🛡️','Baterías'=>'🔋','Soportes'=>'🚗'];
@endphp

<!-- HERO -->
<div class="hero active">
  <div class="hero-inner">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-content">
      <div class="hero-eyebrow">📱 Distribuidores al mayor y al detal</div>
      <h1>Accesorios para tu<br><span class="hero-gradient-text">celular en un click</span></h1>
      <p>Fundas, cargadores, audífonos, protectores y más. Envíos a todo el estado Trujillo. Valera, Estado Trujillo.</p>
      <div class="hero-actions">
        <a href="{{ route('web.catalog') }}" class="btn btn-primary">Ver catálogo completo →</a>
        <a href="{{ route('web.register') }}" class="btn btn-outline">Crear cuenta</a>
      </div>

      <div class="hero-stats">
        <div class="hero-stat">
          <div class="hero-stat-value">{{ $destacados->sum('stock') }}+</div>
          <div class="hero-stat-label">Productos</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-value">2</div>
          <div class="hero-stat-label">Sedes en Valera</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-value">24/7</div>
          <div class="hero-stat-label">Tienda online</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CATEGORY STRIP -->
<div class="cat-strip active">
  <div class="cat-strip-inner">
    <a href="{{ route('web.catalog') }}" class="cat-pill">Todos</a>
    @foreach($categorias as $cat)
      <a href="{{ route('web.catalog') }}?categoria={{ urlencode($cat->nombre) }}" class="cat-pill">{!! ($emojis[$cat->nombre] ?? '') !!} {{ $cat->nombre }}</a>
    @endforeach
  </div>
</div>

<!-- PRODUCT GRID -->
<div class="section active" style="padding-top:1.5rem">
  <div class="products-header">
    <div>
      <div class="products-title">🔥 Más vendidos</div>
    </div>
  </div>

  <div class="product-grid">
    @foreach($destacados as $p)
      @php
        $is_new = optional($p->created_at)->diffInDays(now()) <= 30;
        $stock_bajo = $p->stock <= $p->stock_minimo && $p->stock > 0;
      @endphp
      <a href="{{ route('web.product', $p->slug ?? $p->id) }}" class="product-card" style="text-decoration:none">
        <div class="product-card-img">
          @if($is_new)
            <span class="product-badge badge-new">NUEVO</span>
          @elseif($stock_bajo)
            <span class="product-badge badge-low">POCO STOCK</span>
          @endif
          {!! $emojis[$p->category->nombre ?? ''] ?? '📦' !!}
        </div>
        <div class="product-card-body">
          <div class="product-name">{{ $p->nombre }}</div>
          <div class="product-brand">{{ $p->brand?->nombre ?? '-' }} · {{ $p->category?->nombre ?? '-' }}</div>
          <div class="product-bottom">
            <div class="product-price">${{ number_format($p->precio_detal, 2) }}</div>
            <button class="product-add-btn" @click.stop="$dispatch('agregar-carrito', { producto: {{ json_encode([
                'id'=>$p->id,
                'nombre'=>$p->nombre,
                'precio_detal'=>$p->precio_detal,
                'precio_mayor'=>$p->precio_mayor,
                'emoji'=>$emojis[$p->category->nombre ?? ''] ?? '📦',
                'slug'=>$p->slug
            ]) }}, cantidad: 1, tipo: 'detal' })">+</button>
          </div>
        </div>
      </a>
    @endforeach
  </div>
</div>

@if(isset($recomendados) && $recomendados->isNotEmpty())
<div class="section active" style="margin-top:1.5rem">
  <h3 class="section-title">Recomendados para ti</h3>
  <div class="product-grid">
    @foreach($recomendados as $p)
      @php
        $is_new = optional($p->created_at)->diffInDays(now()) <= 30;
      @endphp
      <a href="{{ route('web.product', $p->slug ?? $p->id) }}" class="product-card" style="text-decoration:none">
        <div class="product-card-img">{!! $emojis[$p->category?->nombre ?? ''] ?? '📦' !!}</div>
        <div class="product-card-body">
          <div class="product-name">{{ $p->nombre }}</div>
          <div class="product-brand">{{ $p->brand?->nombre ?? '-' }} · {{ $p->category?->nombre ?? '-' }}</div>
          <div class="product-bottom">
            <div class="product-price">${{ number_format($p->precio_detal, 2) }}</div>
            <button class="product-add-btn" @click.stop="$dispatch('agregar-carrito', { producto: {{ json_encode([
                'id'=>$p->id,
                'nombre'=>$p->nombre,
                'precio_detal'=>$p->precio_detal,
                'precio_mayor'=>$p->precio_mayor,
                'emoji'=>$emojis[$p->category->nombre ?? ''] ?? '📦',
                'slug'=>$p->slug
            ]) }}, cantidad: 1, tipo: 'detal' })">+</button>
          </div>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endif

@endsection
