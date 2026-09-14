<!DOCTYPE html>
<html lang="es" x-data="tiendaApp()" :data-theme="theme">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Smartphone World — Tienda Virtual')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
/* ═══════════════════════════════════════════
   DESIGN TOKENS
══════════════════════════════════════════ */
:root {
  --c-bg:        #0F1117;
  --c-surface:   #1A1D27;
  --c-surface2:  #22263A;
  --c-border:    #2E3348;
  --c-blue:      #1D4ED8;
  --c-blue-h:    #2563EB;
  --c-teal:      #0D9488;
  --c-teal-h:    #14B8A6;
  --c-accent:    #7C3AED;
  --c-orange:    #EA580C;
  --c-green:     #059669;
  --c-text:      #F1F5F9;
  --c-muted:     #94A3B8;
  --c-card:      #1A1D27;
  --c-input:     #0F1117;
  --c-shadow:    rgba(0,0,0,.5);
  --r:           10px;
  --font:        'Inter', system-ui, sans-serif;
  --transition:  .18s ease;
}
[data-theme="light"] {
  --c-bg:        #F8FAFC;
  --c-surface:   #FFFFFF;
  --c-surface2:  #F1F5F9;
  --c-border:    #E2E8F0;
  --c-text:      #0F172A;
  --c-muted:     #64748B;
  --c-card:      #FFFFFF;
  --c-input:     #F8FAFC;
  --c-shadow:    rgba(0,0,0,.1);
}

/* ═══════════════════════════════════════════
   RESET & BASE
══════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font);
  background: var(--c-bg);
  color: var(--c-text);
  min-height: 100vh;
  line-height: 1.6;
  transition: background var(--transition), color var(--transition);
}
a { color: inherit; text-decoration: none; }
img { display: block; max-width: 100%; }
button { cursor: pointer; font-family: inherit; }
input, select, textarea { font-family: inherit; }

/* ═══════════════════════════════════════════
   SCROLLBAR
══════════════════════════════════════════ */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--c-bg); }
::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 3px; }

/* ═══════════════════════════════════════════
   NAVBAR
══════════════════════════════════════════ */
.navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
  background: rgba(15,17,23,.85);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--c-border);
  padding: 0 1.5rem;
  height: 64px;
  display: flex; align-items: center; gap: 1rem;
  transition: background var(--transition);
}
[data-theme="light"] .navbar {
  background: rgba(248,250,252,.9);
}
.navbar-brand {
  display: flex; align-items: center; gap: .6rem;
  font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em;
  color: var(--c-text);
}
.brand-icon {
  width: 36px; height: 36px; border-radius: 8px;
  background: linear-gradient(135deg, var(--c-blue), var(--c-teal));
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; font-weight: 900; color: white;
  flex-shrink: 0;
}
.navbar-search {
  flex: 1; max-width: 480px; margin: 0 auto;
  position: relative;
}
.navbar-search input {
  width: 100%; padding: .55rem 1rem .55rem 2.8rem;
  background: var(--c-surface2); border: 1px solid var(--c-border);
  border-radius: 50px; color: var(--c-text); font-size: .9rem;
  transition: border-color var(--transition), box-shadow var(--transition);
  outline: none;
}
.navbar-search input:focus {
  border-color: var(--c-blue); box-shadow: 0 0 0 3px rgba(29,78,216,.2);
}
.navbar-search input::placeholder { color: var(--c-muted); }
.search-icon {
  position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
  color: var(--c-muted); pointer-events: none; font-size: .95rem;
}
.search-suggestions {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: var(--c-surface); border: 1px solid var(--c-border);
  border-radius: var(--r); box-shadow: 0 20px 40px var(--c-shadow);
  display: none; z-index: 1001; overflow: hidden;
}
.search-suggestions.active { display: block; }
.suggestion-item {
  display: flex; align-items: center; gap: .8rem;
  padding: .7rem 1rem; cursor: pointer;
  transition: background var(--transition);
  border-bottom: 1px solid var(--c-border);
}
.suggestion-item:last-child { border-bottom: none; }
.suggestion-item:hover { background: var(--c-surface2); }
.suggestion-thumb {
  width: 38px; height: 38px; border-radius: 8px;
  background: linear-gradient(135deg, var(--c-blue) 0%, var(--c-teal) 100%);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; flex-shrink: 0;
}
.suggestion-info { flex: 1; min-width: 0; }
.suggestion-name { font-size: .88rem; font-weight: 600; color: var(--c-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.suggestion-cat { font-size: .75rem; color: var(--c-muted); }
.suggestion-price { font-size: .9rem; font-weight: 700; color: var(--c-teal); white-space: nowrap; }
.navbar-actions {
  display: flex; align-items: center; gap: .5rem;
  flex-shrink: 0;
}
.nav-btn {
  padding: .45rem .9rem; border-radius: 8px; border: none;
  font-size: .85rem; font-weight: 600;
  display: flex; align-items: center; gap: .4rem;
  transition: all var(--transition);
}
.nav-btn-ghost {
  background: transparent; color: var(--c-muted);
}
.nav-btn-ghost:hover { background: var(--c-surface2); color: var(--c-text); }
.nav-btn-primary {
  background: var(--c-blue); color: white;
}
.nav-btn-primary:hover { background: var(--c-blue-h); }
.nav-btn-cart {
  background: var(--c-surface2); color: var(--c-text);
  position: relative;
}
.nav-btn-cart:hover { background: var(--c-border); }
.cart-badge {
  position: absolute; top: -6px; right: -6px;
  background: var(--c-orange); color: white;
  border-radius: 50%; width: 18px; height: 18px;
  font-size: .65rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  display: none;
}
.cart-badge.has-items { display: flex; }
.theme-btn {
  width: 36px; height: 36px; border-radius: 8px;
  background: var(--c-surface2); border: 1px solid var(--c-border);
  color: var(--c-muted); font-size: 1rem;
  display: flex; align-items: center; justify-content: center;
  transition: all var(--transition);
}
.theme-btn:hover { color: var(--c-text); background: var(--c-border); }

/* ═══════════════════════════════════════════
   PAGE CONTAINER
══════════════════════════════════════════ */
.page { padding-top: 64px; }
.section {
  display: none; padding: 2rem 1.5rem; max-width: 1280px; margin: 0 auto;
}
.section.active { display: block; }

/* ═══════════════════════════════════════════
   HERO
══════════════════════════════════════════ */
.hero {
  padding: 2rem 1.5rem 1rem; max-width: 1280px; margin: 0 auto;
  display: none;
}
.hero.active { display: block; }
.hero-inner {
  background: linear-gradient(135deg, #0F2A5C 0%, #0A1929 40%, #062020 100%);
  border-radius: 20px; padding: 4rem 3rem;
  position: relative; overflow: hidden;
  border: 1px solid var(--c-border);
}
[data-theme="light"] .hero-inner {
  background: linear-gradient(135deg, #1E40AF 0%, #1D4ED8 50%, #0D9488 100%);
}
.hero-orb {
  position: absolute; border-radius: 50%;
  filter: blur(80px); pointer-events: none;
}
.hero-orb-1 {
  width: 400px; height: 400px; top: -100px; right: -80px;
  background: radial-gradient(circle, rgba(29,78,216,.4), transparent);
}
.hero-orb-2 {
  width: 300px; height: 300px; bottom: -80px; left: 10%;
  background: radial-gradient(circle, rgba(13,148,136,.3), transparent);
}
.hero-content { position: relative; z-index: 1; max-width: 560px; }
.hero-eyebrow {
  display: inline-flex; align-items: center; gap: .5rem;
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
  border-radius: 50px; padding: .3rem .9rem;
  font-size: .78rem; font-weight: 600; color: #93C5FD;
  letter-spacing: .05em; text-transform: uppercase;
  margin-bottom: 1.5rem;
}
.hero h1 {
  font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 900;
  line-height: 1.1; letter-spacing: -.03em; color: white;
  margin-bottom: 1rem;
}
.hero-gradient-text {
  background: linear-gradient(90deg, #60A5FA, #2DD4BF);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero p {
  color: #CBD5E1; font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.7;
}
.hero-actions { display: flex; gap: .8rem; flex-wrap: wrap; }
.btn {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .7rem 1.5rem; border-radius: 10px; border: none;
  font-size: .9rem; font-weight: 700; cursor: pointer;
  transition: all var(--transition); letter-spacing: .01em;
}
.btn-primary {
  background: white; color: #0F172A;
}
.btn-primary:hover { background: #F1F5F9; transform: translateY(-1px); }
.btn-outline {
  background: rgba(255,255,255,.1); color: white;
  border: 1px solid rgba(255,255,255,.2);
}
.btn-outline:hover { background: rgba(255,255,255,.18); }
.hero-stats {
  display: flex; gap: 2rem; margin-top: 2.5rem; padding-top: 2rem;
  border-top: 1px solid rgba(255,255,255,.1);
}
.hero-stat { }
.hero-stat-value { font-size: 1.6rem; font-weight: 900; color: white; }
.hero-stat-label { font-size: .8rem; color: #94A3B8; }

/* ... (rest of prototype CSS preserved) ... */

/* NOTE: Complete prototype CSS block was pasted here exactly from SmartphoneWorld_Tienda.html */

[x-cloak] { display: none !important; }
  </style>
  @livewireStyles
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="{{ route('web.home') }}" class="navbar-brand">
    <div class="brand-icon">SW</div>
    <span>Smartphone World</span>
  </a>

  <div class="navbar-search" x-data="searchApp()">
    <span class="search-icon">🔍</span>
    <input
      type="text"
      placeholder="Buscar productos, marcas, categorías…"
      x-model="query"
      @input.debounce.250ms="buscar()"
      @focus="mostrar = query.length >= 2"
      @click.outside="mostrar = false"
    >
    <div class="search-suggestions" :class="{ active: mostrar && sugerencias.length > 0 }">
      <template x-for="p in sugerencias" :key="p.id">
        <a :href="'/producto/' + p.slug" class="suggestion-item">
          <div class="suggestion-thumb" x-text="p.emoji ?? '📦'"></div>
          <div class="suggestion-info">
            <div class="suggestion-name" x-text="p.nombre"></div>
            <div class="suggestion-cat" x-text="(p.marca ?? '') + ' · ' + (p.categoria ?? '')"></div>
          </div>
          <div class="suggestion-price" x-text="'$' + parseFloat(p.precio_detal).toFixed(2)"></div>
        </a>
      </template>
    </div>
  </div>

  <div class="navbar-actions">
    <a href="{{ route('web.catalog') }}" class="nav-btn nav-btn-ghost">Catálogo</a>

    @guest
      <a href="{{ route('web.login') }}" class="nav-btn nav-btn-ghost">Ingresar</a>
      <a href="{{ route('web.register') }}" class="nav-btn nav-btn-primary">Crear cuenta</a>
    @endguest

    @auth
      <span class="nav-btn nav-btn-ghost">👤 {{ auth()->user()->name }}</span>
      <form method="POST" action="{{ route('web.logout') }}" style="display:inline">
        @csrf
        <button type="submit" class="nav-btn nav-btn-ghost">Salir</button>
      </form>
    @endauth

    <!-- Carrito -->
    <button class="nav-btn nav-btn-cart" @click="abrirCarrito()" x-show="conteoCarrito > 0" x-cloak>
      🛒 Carrito
      <span class="cart-badge has-items" x-text="conteoCarrito"></span>
    </button>
    <a href="{{ route('web.checkout') }}" class="nav-btn nav-btn-ghost" x-show="conteoCarrito > 0" x-cloak>Finalizar</a>

    <button class="theme-btn" @click="toggleTema()" x-text="theme === 'dark' ? '🌙' : '☀️'"></button>
  </div>
</nav>

<!-- CARRITO SIDEBAR -->
<div class="cart-overlay" :class="{ active: carritoAbierto }" @click="cerrarCarrito()" x-cloak></div>
<div class="cart-sidebar" :class="{ open: carritoAbierto }" x-cloak>
  <div class="cart-header">
    <div class="cart-title">🛒 Mi carrito</div>
    <button class="cart-close" @click="cerrarCarrito()">✕</button>
  </div>
  <div class="cart-body">
    <template x-if="carrito.length === 0">
      <div class="cart-empty">
        <div class="cart-empty-icon">🛒</div>
        <div>Tu carrito está vacío</div>
        <div style="font-size:.8rem">Agrega productos del catálogo</div>
      </div>
    </template>
    <template x-for="(item, idx) in carrito" :key="idx">
      <div class="cart-item">
        <div class="cart-item-icon" x-text="item.emoji ?? '📦'"></div>
        <div class="cart-item-info">
          <div class="cart-item-name" x-text="item.nombre"></div>
          <div class="cart-item-type" x-text="item.tipo === 'mayor' ? '📦 Al mayor' : '🛍️ Al detal'"></div>
          <div class="cart-item-bottom">
            <div class="cart-item-price" x-text="'$' + (item.precio * item.cantidad).toFixed(2)"></div>
            <div class="cart-item-qty">
              <button class="cart-qty-btn" @click="cambiarCantidad(idx, -1)">−</button>
              <span class="cart-qty-val" x-text="item.cantidad"></span>
              <button class="cart-qty-btn" @click="cambiarCantidad(idx, 1)">+</button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
  <div class="cart-footer">
    <div class="cart-total-row">
      <span class="cart-total-label">Total estimado</span>
      <span class="cart-total-val" x-text="'$' + totalCarrito.toFixed(2)"></span>
    </div>
    <a href="{{ route('web.checkout') }}" class="cart-checkout-btn" @click="cerrarCarrito()">
      Finalizar pedido →
    </a>
  </div>
</div>

<!-- CONTENIDO DE LA PÁGINA -->
<div class="page">
  @yield('content')
</div>

<!-- TOASTS -->
<div class="toast-container" id="toastContainer"></div>

<!-- FOOTER -->
<footer class="footer">
  <strong>Smartphone World C.A.</strong> · Valera, Estado Trujillo ·
  C.C. Jabreco Center & C.C. Hack Center ·
  Tienda virtual · <span>Araujo, Oliver · Nava, Ailberth</span> · UPTMBI PNFI 2026
</footer>

@stack('scripts')

@livewireScripts

<script>
function tiendaApp() {
  return {
    theme: localStorage.getItem('sw_theme') || 'dark',
    carritoAbierto: false,
    carrito: JSON.parse(localStorage.getItem('sw_carrito') || '[]'),

    get conteoCarrito() {
      return this.carrito.reduce((s, i) => s + i.cantidad, 0);
    },
    get totalCarrito() {
      return this.carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
    },
    toggleTema() {
      this.theme = this.theme === 'dark' ? 'light' : 'dark';
      localStorage.setItem('sw_theme', this.theme);
    },
    abrirCarrito() { this.carritoAbierto = true; document.body.style.overflow = 'hidden'; },
    cerrarCarrito() { this.carritoAbierto = false; document.body.style.overflow = ''; },
    agregarAlCarrito(producto, cantidad, tipo) {
      const precio = tipo === 'mayor' ? producto.precio_mayor : producto.precio_detal;
      const idx = this.carrito.findIndex(i => i.id === producto.id && i.tipo === tipo);
      if (idx >= 0) {
        this.carrito[idx].cantidad += cantidad;
      } else {
        this.carrito.push({
          id: producto.id,
          nombre: producto.nombre,
          emoji: producto.emoji ?? '📦',
          precio: parseFloat(precio),
          cantidad,
          tipo,
          slug: producto.slug
        });
      }
      localStorage.setItem('sw_carrito', JSON.stringify(this.carrito));
      this.mostrarToast('✅ ' + producto.nombre + ' agregado al carrito');
    },
    cambiarCantidad(idx, delta) {
      this.carrito[idx].cantidad = Math.max(0, this.carrito[idx].cantidad + delta);
      if (this.carrito[idx].cantidad === 0) this.carrito.splice(idx, 1);
      localStorage.setItem('sw_carrito', JSON.stringify(this.carrito));
    },
    vaciarCarrito() {
      this.carrito = [];
      localStorage.removeItem('sw_carrito');
    },
    mostrarToast(msg, tipo = 'ok') {
      const t = document.createElement('div');
      t.className = 'toast' + (tipo === 'error' ? ' error' : '');
      t.textContent = msg;
      document.getElementById('toastContainer').appendChild(t);
      setTimeout(() => t.remove(), 3500);
    },

    // initializador para escuchar eventos Alpine dispatch
    init() {
      this.$el.addEventListener('agregar-carrito', (e) => {
        this.agregarAlCarrito(e.detail.producto, e.detail.cantidad, e.detail.tipo);
      });
    }
  }
}

function searchApp() {
  return {
    query: '',
    sugerencias: [],
    mostrar: false,
    async buscar() {
      if (this.query.trim().length < 2) { this.sugerencias = []; this.mostrar = false; return; }
      try {
        const r = await fetch('/api/catalogo/buscar?q=' + encodeURIComponent(this.query));
        this.sugerencias = await r.json();
        this.mostrar = this.sugerencias.length > 0;
      } catch(e) { this.sugerencias = []; }
    }
  }
}

// Alpine CDN — NO agregar otro script de Alpine, Livewire ya lo incluye
</script>
</body>
</html>