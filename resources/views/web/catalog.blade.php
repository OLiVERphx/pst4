@extends('layouts.web')

@section('content')

<div x-data="catalogoApp(@json($productosJson))" class="section active">

  <!-- BREADCRUMB -->
  <div class="breadcrumb">
    <a href="{{ route('web.home') }}">Inicio</a>
    <span class="sep">›</span>
    <span>Catálogo</span>
  </div>

  <!-- CATEGORY STRIP -->
  <div class="cat-strip active">
    <div class="cat-strip-inner">
      <button class="cat-pill" type="button" @click="filterCat('all')">Todos</button>
      @foreach($categorias as $cat)
        <button class="cat-pill" type="button" @click="filterCat('{{ $cat->nombre }}')">{!! ($emojis[$cat->nombre] ?? '') !!} {{ $cat->nombre }}</button>
      @endforeach
    </div>
  </div>

  <!-- HEADER -->
  <div class="products-header">
    <div>
      <div class="products-title" x-text="filtroActual === 'all' ? 'Todos los productos' : filtroActual"></div>
      <div class="products-count" x-text="productosFiltrados.length + ' productos'"></div>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="filter-row">
    <select class="filter-select" x-model="filtroMarca" @change="aplicarFiltros()">
      <option value="">Todas las marcas</option>
      @foreach($marcas as $m)
        <option value="{{ $m->nombre }}">{{ $m->nombre }}</option>
      @endforeach
    </select>
    <select class="filter-select" x-model="orden" @change="aplicarFiltros()">
      <option value="default">Ordenar por</option>
      <option value="precio-asc">Menor precio</option>
      <option value="precio-desc">Mayor precio</option>
      <option value="nombre">Nombre A–Z</option>
    </select>
  </div>

  <!-- GRID -->
  <div class="product-grid">
    <template x-for="p in productosFiltrados" :key="p.id">
      <div class="product-card" @click="abrirModal(p)">
        <div class="product-card-img">
          <span x-show="p.es_nuevo" class="product-badge badge-new">NUEVO</span>
          <span x-show="p.stock_bajo && !p.es_nuevo" class="product-badge badge-low">POCO STOCK</span>
          <span style="font-size:4rem" x-text="p.emoji"></span>
        </div>
        <div class="product-card-body">
          <div class="product-name" x-text="p.nombre"></div>
          <div class="product-brand" x-text="(p.marca ?? '') + ' · ' + (p.categoria ?? '')"></div>
          <div class="product-bottom">
            <div class="product-price" x-text="'$' + p.precio_detal.toFixed(2)"></div>
            <button class="product-add-btn"
              :class="{ added: estaEnCarrito(p.id) }"
              @click.stop="agregarRapido(p)"
              x-text="estaEnCarrito(p.id) ? '✓' : '+'">
            </button>
          </div>
        </div>
      </div>
    </template>

    <template x-if="productosFiltrados.length === 0">
      <div class="no-results" style="grid-column:1/-1">
        <div class="no-results-icon">🔍</div>
        <h3>Sin resultados</h3>
        <p>No hay productos para los filtros seleccionados</p>
      </div>
    </template>
  </div>

  <!-- MODAL DE PRODUCTO -->
  <div class="modal-overlay" :class="{ active: modalAbierto }"
       @click.self="cerrarModal()" @keydown.escape.window="cerrarModal()">
    <div class="modal" style="position:relative" x-show="modalAbierto">
      <button class="modal-close" @click="cerrarModal()">✕</button>
      <div class="modal-img">
        <span style="font-size:6rem" x-text="productoModal?.emoji"></span>
      </div>
      <div class="modal-body" x-show="productoModal">
        <div class="modal-brand" x-text="productoModal?.marca"></div>
        <div class="modal-name" x-text="productoModal?.nombre"></div>
        <div class="modal-cat" x-text="(productoModal?.categoria ?? '') + ' · Código: ' + (productoModal?.codigo ?? '')"></div>
        <div class="modal-price-row">
          <div class="modal-price" x-text="'$' + productoModal?.precio_detal.toFixed(2)"></div>
          <div class="modal-price-mayor" x-text="'Mayor: $' + productoModal?.precio_mayor.toFixed(2)"></div>
        </div>
        <div class="modal-stock">
          <div class="stock-dot"
            :style="productoModal?.stock > productoModal?.stock_minimo ? 'background:var(--c-green)' :
                    productoModal?.stock > 0 ? 'background:var(--c-orange)' : 'background:#EF4444'">
          </div>
          <span x-text="productoModal?.stock > productoModal?.stock_minimo
            ? productoModal?.stock + ' unidades disponibles'
            : productoModal?.stock > 0
              ? 'Solo ' + productoModal?.stock + ' unidades'
              : 'Sin stock'">
          </span>
        </div>
        <div class="modal-qty-row">
          <span class="qty-label">Cantidad:</span>
          <div class="qty-ctrl">
            <button class="qty-btn" @click="modalCantidad = Math.max(1, modalCantidad-1)">−</button>
            <span class="qty-val" x-text="modalCantidad"></span>
            <button class="qty-btn" @click="modalCantidad++">+</button>
          </div>
        </div>
        <div class="modal-type-row">
          <button class="type-btn" :class="{ active: modalTipo==='detal' }"
            @click="modalTipo='detal'">🛍️ Al detal</button>
          <button class="type-btn" :class="{ active: modalTipo==='mayor' }"
            @click="modalTipo='mayor'">📦 Al mayor</button>
        </div>
        <button class="modal-add-btn" @click="agregarDesdeModal()">
          Agregar al carrito
        </button>
        <div class="modal-specs">
          <div class="modal-specs-title">Especificaciones</div>
          <div class="spec-row"><span class="spec-key">Código</span><span class="spec-val" x-text="productoModal?.codigo"></span></div>
          <div class="spec-row"><span class="spec-key">Marca</span><span class="spec-val" x-text="productoModal?.marca"></span></div>
          <div class="spec-row"><span class="spec-key">Precio detal</span><span class="spec-val" x-text="'$'+productoModal?.precio_detal.toFixed(2)"></span></div>
          <div class="spec-row"><span class="spec-key">Precio mayor</span><span class="spec-val" x-text="'$'+productoModal?.precio_mayor.toFixed(2)"></span></div>
          <div class="spec-row"><span class="spec-key">Stock</span><span class="spec-val" x-text="productoModal?.stock + ' uds'"></span></div>
        </div>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
function catalogoApp(productos) {
  return {
    todosLosProductos: productos,
    productosFiltrados: productos,
    filtroActual: '{{ request("categoria","all") }}' || 'all',
    filtroMarca: '{{ request("marca","") }}',
    orden: '{{ request("orden","default") }}',
    modalAbierto: false,
    productoModal: null,
    modalCantidad: 1,
    modalTipo: 'detal',

    init() {
      this.aplicarFiltros();
    },
    filterCat(cat) {
      this.filtroActual = cat;
      this.aplicarFiltros();
    },
    aplicarFiltros() {
      let r = [...this.todosLosProductos];
      if (this.filtroActual !== 'all' && this.filtroActual !== '') {
        r = r.filter(p => p.categoria === this.filtroActual);
      }
      if (this.filtroMarca) {
        r = r.filter(p => p.marca === this.filtroMarca);
      }
      if (this.orden === 'precio-asc') r.sort((a,b) => a.precio_detal - b.precio_detal);
      else if (this.orden === 'precio-desc') r.sort((a,b) => b.precio_detal - a.precio_detal);
      else if (this.orden === 'nombre') r.sort((a,b) => a.nombre.localeCompare(b.nombre));
      this.productosFiltrados = r;
    },
    abrirModal(p) {
      this.productoModal = p;
      this.modalCantidad = 1;
      this.modalTipo = 'detal';
      this.modalAbierto = true;
      document.body.style.overflow = 'hidden';
    },
    cerrarModal() {
      this.modalAbierto = false;
      document.body.style.overflow = '';
    },
    estaEnCarrito(id) {
      const carrito = JSON.parse(localStorage.getItem('sw_carrito') || '[]');
      return carrito.some(i => i.id === id);
    },
    agregarRapido(p) {
      this.$dispatch('agregar-carrito', { producto: p, cantidad: 1, tipo: 'detal' });
    },
    agregarDesdeModal() {
      if (!this.productoModal) return;
      this.$dispatch('agregar-carrito', {
        producto: this.productoModal,
        cantidad: this.modalCantidad,
        tipo: this.modalTipo
      });
      this.cerrarModal();
    }
  }
}
</script>
@endpush

@endsection
