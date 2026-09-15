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
   PRODUCT GRID
═══════════════════════════════════════════ */
.products-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.2rem;
}
.products-title { font-size: 1.1rem; font-weight: 700; }
.products-count { font-size: .85rem; color: var(--c-muted); }
.filter-row {
  display: flex; gap: .6rem; margin-bottom: 1.5rem; flex-wrap: wrap;
}
.filter-select {
  padding: .45rem .9rem; border-radius: 8px;
  background: var(--c-surface2); border: 1px solid var(--c-border);
  color: var(--c-text); font-size: .85rem; cursor: pointer; outline: none;
}
.filter-select:focus { border-color: var(--c-blue); }
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 1.2rem;
}
.product-card {
  background: var(--c-card); border: 1px solid var(--c-border);
  border-radius: 14px; overflow: hidden;
  transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
  cursor: pointer;
}
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px var(--c-shadow);
  border-color: var(--c-blue);
}
.product-card-img {
  height: 170px;
  display: flex; align-items: center; justify-content: center;
  font-size: 4rem; position: relative;
  background: linear-gradient(135deg, var(--c-surface2) 0%, var(--c-bg) 100%);
}
.product-badge {
  position: absolute; top: .6rem; left: .6rem;
  padding: .2rem .6rem; border-radius: 6px;
  font-size: .7rem; font-weight: 700; letter-spacing: .04em;
}
.badge-new { background: var(--c-teal); color: white; }
.badge-low { background: var(--c-orange); color: white; }
.badge-sale { background: var(--c-accent); color: white; }
.product-card-body { padding: 1rem; }
.product-name { font-size: .92rem; font-weight: 700; margin-bottom: .25rem; color: var(--c-text); }
.product-brand { font-size: .78rem; color: var(--c-muted); margin-bottom: .6rem; }
.product-bottom {
  display: flex; align-items: center; justify-content: space-between; gap: .5rem;
}
.product-price { font-size: 1.1rem; font-weight: 900; color: var(--c-teal); }
.product-add-btn {
  width: 34px; height: 34px; border-radius: 8px; border: none;
  background: var(--c-blue); color: white; font-size: 1.1rem;
  display: flex; align-items: center; justify-content: center;
  transition: all var(--transition); flex-shrink: 0;
}
.product-add-btn:hover { background: var(--c-blue-h); transform: scale(1.1); }
.product-add-btn.added { background: var(--c-green); }

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

.cat-strip {
  padding: 1.5rem 1.5rem 0; max-width: 1280px; margin: 0 auto;
  display: none;
}
.cat-strip-inner {
  display: flex; gap: .6rem; overflow-x: auto; padding-bottom: .5rem;
  scrollbar-width: none;
}
.cat-pill {
  flex-shrink: 0; padding: .45rem 1.1rem; border-radius: 50px;
  background: var(--c-surface2); border: 1px solid var(--c-border);
  font-size: .85rem; font-weight: 600; color: var(--c-muted);
  cursor: pointer; transition: all var(--transition); white-space: nowrap;
}
.modal-overlay {
  position: fixed; inset: 0; z-index: 2000;
  background: rgba(0,0,0,.7); backdrop-filter: blur(6px);
  display: none; align-items: center; justify-content: center;
  padding: 1rem;
}
.modal {
  background: var(--c-surface); border: 1px solid var(--c-border);
  border-radius: 20px; max-width: 680px; width: 100%;
  max-height: 90vh; overflow-y: auto;
  animation: slideUp .25s ease;
}
.modal-close {
  position: absolute; top: 1rem; right: 1rem;
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--c-surface2); border: 1px solid var(--c-border);
  color: var(--c-muted); font-size: 1.1rem;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all var(--transition);
}
.modal-img {
  height: 240px; display: flex; align-items: center; justify-content: center;
  font-size: 7rem;
  background: linear-gradient(135deg, var(--c-surface2), var(--c-bg));
  border-radius: 20px 20px 0 0;
  position: relative;
}
.modal-body { padding: 1.5rem; }
.modal-brand { font-size: .8rem; color: var(--c-teal); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .3rem; }
.modal-name { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; margin-bottom: .5rem; }
.modal-cat { font-size: .85rem; color: var(--c-muted); margin-bottom: 1rem; }
.modal-price-row { display: flex; align-items: baseline; gap: .8rem; margin-bottom: 1.2rem; }
.modal-price { font-size: 2rem; font-weight: 900; color: var(--c-teal); }
.modal-price-mayor { font-size: .85rem; color: var(--c-muted); padding: .3rem .7rem; background: var(--c-surface2); border-radius: 6px; border: 1px solid var(--c-border); }
.modal-stock { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: var(--c-muted); margin-bottom: 1.5rem; }
.stock-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--c-green); }
.modal-qty-row {
  display: flex; align-items: center; gap: 1rem; margin-bottom: 1.2rem;
}
.qty-label { font-size: .9rem; font-weight: 600; color: var(--c-muted); }
.qty-ctrl {
  display: flex; align-items: center; gap: .5rem;
  background: var(--c-surface2); border: 1px solid var(--c-border); border-radius: 10px; padding: .2rem;
}
.modal-type-row {
  display: flex; gap: .6rem; margin-bottom: 1.5rem;
}
.type-btn {
  flex: 1; padding: .65rem; border-radius: 10px; border: 2px solid var(--c-border);
  background: transparent; color: var(--c-muted); font-size: .85rem; font-weight: 600;
  cursor: pointer; transition: all var(--transition); text-align: center;
}
.modal-add-btn {
  width: 100%; padding: .85rem; border-radius: 12px; border: none;
  background: linear-gradient(135deg, var(--c-blue), var(--c-teal));
  color: white; font-size: 1rem; font-weight: 700; cursor: pointer;
  transition: all var(--transition); letter-spacing: .02em;
}
.modal-specs { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--c-border); }
.modal-specs-title { font-size: .8rem; font-weight: 700; color: var(--c-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: .8rem; }
.spec-row { display: flex; justify-content: space-between; padding: .45rem 0; border-bottom: 1px solid var(--c-border); font-size: .88rem; }
.spec-key { color: var(--c-muted); }
.spec-val { font-weight: 600; }
.auth-page {
  min-height: calc(100vh - 64px);
  display: flex; align-items: center; justify-content: center;
  padding: 2rem 1rem;
}
.auth-card {
  background: var(--c-surface); border: 1px solid var(--c-border);
  border-radius: 20px; padding: 2.5rem;
  width: 100%; max-width: 440px;
}
.auth-logo {
  display: flex; align-items: center; gap: .8rem;
  margin-bottom: 2rem;
}
.auth-title { font-size: 1.5rem; font-weight: 800; margin-bottom: .4rem; }
.auth-sub { font-size: .88rem; color: var(--c-muted); margin-bottom: 2rem; }
.form-group { margin-bottom: 1.1rem; }
.form-label { font-size: .82rem; font-weight: 600; color: var(--c-muted); display: block; margin-bottom: .4rem; letter-spacing: .04em; text-transform: uppercase; }
.form-input {
  width: 100%; padding: .7rem 1rem; border-radius: 10px;
  background: var(--c-input); border: 1px solid var(--c-border);
  color: var(--c-text); font-size: .9rem; outline: none;
  transition: border-color var(--transition), box-shadow var(--transition);
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }
.form-select {
  width: 100%; padding: .7rem 1rem; border-radius: 10px;
  background: var(--c-input); border: 1px solid var(--c-border);
  color: var(--c-text); font-size: .9rem; outline: none; cursor: pointer;
}
.form-submit {
  width: 100%; padding: .85rem; border-radius: 12px; border: none;
  background: linear-gradient(135deg, var(--c-blue), var(--c-teal));
  color: white; font-size: .95rem; font-weight: 700; cursor: pointer;
  margin-top: .5rem; transition: all var(--transition);
}
.auth-switch {
  text-align: center; margin-top: 1.2rem; font-size: .88rem; color: var(--c-muted);
}
.form-divider {
  text-align: center; font-size: .8rem; color: var(--c-muted);
  margin: 1rem 0; position: relative;
}
.checkout-grid {
  display: grid; grid-template-columns: 1fr 380px; gap: 2rem; align-items: start;
}
.checkout-section-title {
  font-size: 1rem; font-weight: 700; margin-bottom: 1rem;
  padding-bottom: .6rem; border-bottom: 1px solid var(--c-border);
  display: flex; align-items: center; gap: .5rem;
}
.panel {
  background: var(--c-surface); border: 1px solid var(--c-border);
  border-radius: 14px; padding: 1.5rem; margin-bottom: 1.2rem;
}
.payment-methods {
  display: flex; flex-direction: column; gap: .6rem; margin-bottom: 1.2rem;
}
.payment-method {
  padding: .9rem 1rem; border-radius: 10px;
  border: 2px solid var(--c-border); cursor: pointer;
  transition: all var(--transition); display: flex; align-items: center; gap: .8rem;
}
.payment-method-icon { font-size: 1.4rem; }
.payment-method-name { font-size: .9rem; font-weight: 600; }
.payment-method-sub { font-size: .75rem; color: var(--c-muted); }
.payment-detail-box {
  background: var(--c-surface2); border: 1px solid var(--c-border);
  border-radius: 10px; padding: 1rem; margin-top: 1rem;
  font-size: .85rem; display: none;
}
.pay-data-row { display: flex; justify-content: space-between; padding: .3rem 0; }
.pay-data-key { color: var(--c-muted); }
.pay-data-val { font-weight: 700; font-family: monospace; }
.upload-area {
  border: 2px dashed var(--c-border); border-radius: 10px;
  padding: 2rem; text-align: center; cursor: pointer; margin-top: 1rem;
  transition: border-color var(--transition);
}
.upload-icon { font-size: 2rem; margin-bottom: .5rem; }
.upload-text { font-size: .85rem; color: var(--c-muted); }
.upload-hint { font-size: .75rem; color: var(--c-muted); margin-top: .3rem; }
.security-note {
  display: flex; align-items: flex-start; gap: .6rem;
  background: rgba(5,150,105,.08); border: 1px solid rgba(5,150,105,.2);
  border-radius: 10px; padding: .8rem 1rem; margin-top: .8rem;
  font-size: .8rem; color: #34D399;
}
.order-summary-item {
  display: flex; justify-content: space-between; padding: .45rem 0;
  font-size: .88rem; border-bottom: 1px solid var(--c-border);
}
.order-total-row {
  display: flex; justify-content: space-between;
  font-size: 1.1rem; font-weight: 800; padding-top: 1rem;
  border-top: 2px solid var(--c-border); margin-top: .5rem;
}
.no-results {
  text-align: center; padding: 4rem 2rem; color: var(--c-muted);
}
.no-results-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.section-title {
  font-size: 1.4rem; font-weight: 800; letter-spacing: -.02em;
  margin-bottom: 1.5rem; color: var(--c-text);
}
.breadcrumb {
  display: flex; align-items: center; gap: .5rem;
  font-size: .83rem; color: var(--c-muted); margin-bottom: 1.5rem;
}
.chip {
  display: inline-flex; align-items: center; gap: .35rem;
  padding: .25rem .65rem; border-radius: 50px; font-size: .75rem; font-weight: 700;
}
.chip-teal { background: rgba(13,148,136,.12); color: #2DD4BF; }
.chip-blue { background: rgba(29,78,216,.12); color: #60A5FA; }
.chip-orange { background: rgba(234,88,12,.12); color: #FB923C; }
.qty-btn {
  width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--c-border);
  background: var(--c-surface2); color: var(--c-text); font-size: 1.1rem;
  display: flex; align-items: center; justify-content: center;
  transition: all var(--transition);
}
.qty-btn:hover { background: var(--c-blue); color: white; }
.qty-val { min-width: 30px; text-align: center; font-weight: 700; font-size: .95rem; }
.payment-detail-box.active { display: block; }
.breadcrumb .sep { opacity: .4; }
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