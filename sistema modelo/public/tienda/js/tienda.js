/* tienda.js - extracted from prototype. Expects const PRODUCTS to be injected before this script */

const CATEGORIES = ['Audífonos', 'Fundas', 'Cargadores', 'Cables', 'Protectores', 'Baterías', 'Soportes'];
let BRANDS = (typeof PRODUCTS !== 'undefined') ? [...new Set(PRODUCTS.map(p => p.brand))] : [];

/* ═══════════════════════════════════════════
   STATE
═══════════════════════════════════════════ */
let cart = [];
let currentModal = null;
let modalQty = 1;
let modalType = 'detal';
let currentFilter = 'all';
let isLoggedIn = false;
let currentUser = null;
let orderCount = 1;

/* ═══════════════════════════════════════════
   NAVIGATION
═══════════════════════════════════════════ */
function navigate(page) {
  const allSections = document.querySelectorAll('.section');
  allSections.forEach(s => s.classList.remove('active'));

  const hero = document.getElementById('heroSection');
  const catStrip = document.getElementById('catStrip');
  const homeSection = document.getElementById('homeCatalogSection');

  hero.classList.remove('active');
  catStrip.classList.remove('active');
  homeSection.classList.remove('active');

  if (page === 'home') {
    hero.classList.add('active');
    catStrip.classList.add('active');
    homeSection.classList.add('active');
    renderHomeProducts();
    renderCatStrip();
  } else if (page === 'catalog') {
    catStrip.classList.add('active');
    document.getElementById('catalogSection').classList.add('active');
    renderCatalog();
    renderCatStrip();
  } else if (page === 'login') {
    document.getElementById('loginSection').classList.add('active');
  } else if (page === 'register') {
    document.getElementById('registerSection').classList.add('active');
  } else if (page === 'checkout') {
    if (!isLoggedIn) {
      showToast('⚠️ Debes iniciar sesión para continuar', 'error');
      navigate('login');
      return;
    }
    document.getElementById('checkoutSection').classList.add('active');
    renderOrderSummary();
  } else if (page === 'confirmed') {
    document.getElementById('confirmedSection').classList.add('active');
    document.getElementById('orderNum').textContent = '#SW-' + String(orderCount++).padStart(4,'0');
  }

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ═══════════════════════════════════════════
   CATEGORY STRIP
═══════════════════════════════════════════ */
function renderCatStrip() {
  const inner = document.getElementById('catStripInner');
  inner.innerHTML = `<div class="cat-pill ${currentFilter==='all'?'active':''}" onclick="filterCat('all', this)">Todos</div>`;
  CATEGORIES.forEach(cat => {
    inner.innerHTML += `<div class="cat-pill ${currentFilter===cat?'active':''}" onclick="filterCat('${cat}', this)">${cat}</div>`;
  });
}

function filterCat(cat, el) {
  currentFilter = cat;
  document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.cat-pill').forEach(p => {
    if (p.textContent.trim() === (cat === 'all' ? 'Todos' : cat)) p.classList.add('active');
  });
  const catalogSection = document.getElementById('catalogSection');
  if (catalogSection.classList.contains('active')) {
    renderCatalog();
  } else {
    renderHomeProducts();
  }
}

/* ═══════════════════════════════════════════
   RENDER: HOME PRODUCTS
═══════════════════════════════════════════ */
function renderHomeProducts() {
  const grid = document.getElementById('homeProductGrid');
  const featured = PRODUCTS.slice(0, 8);
  grid.innerHTML = featured.map(p => productCard(p)).join('');
}

function renderCatalog() {
  let filtered = currentFilter === 'all' ? [...PRODUCTS] : PRODUCTS.filter(p => p.category === currentFilter);

  // Brand filter
  const brandVal = document.getElementById('filterBrand')?.value;
  if (brandVal) filtered = filtered.filter(p => p.brand === brandVal);

  // Sort
  const sortVal = document.getElementById('filterSort')?.value;
  if (sortVal === 'price-asc') filtered.sort((a,b) => a.price - b.price);
  else if (sortVal === 'price-desc') filtered.sort((a,b) => b.price - a.price);
  else if (sortVal === 'name') filtered.sort((a,b) => a.name.localeCompare(b.name));

  document.getElementById('catalogTitle').textContent = currentFilter === 'all' ? 'Todos los productos' : currentFilter;
  document.getElementById('catalogCount').textContent = filtered.length + ' productos';

  const grid = document.getElementById('catalogProductGrid');
  if (filtered.length === 0) {
    grid.innerHTML = `<div class="no-results" style="grid-column:1/-1">
      <div class="no-results-icon">🔍</div>
      <h3>Sin resultados</h3>
      <p>No hay productos para los filtros seleccionados</p>
    </div>`;
    return;
  }
  grid.innerHTML = filtered.map(p => productCard(p)).join('');

  // Populate brand filter
  const fb = document.getElementById('filterBrand');
  const currentBrand = fb.value;
  const brands = [...new Set(
    (currentFilter === 'all' ? PRODUCTS : PRODUCTS.filter(p => p.category === currentFilter)).map(p => p.brand)
  )].sort();
  fb.innerHTML = '<option value="">Todas las marcas</option>' + brands.map(b => `<option value="${b}" ${b===currentBrand?'selected':''}>${b}</option>`).join('');
}

function applyFilters() { renderCatalog(); }

function productCard(p) {
  const inCart = cart.some(c => c.id === p.id);
  const lowStock = p.qty <= p.minQty && p.qty > 0;
  const badge = p.isNew ? '<span class="product-badge badge-new">NUEVO</span>' : (lowStock ? '<span class="product-badge badge-low">POCO STOCK</span>' : '');
  return `
    <div class="product-card" onclick="openModal(${p.id})">
      <div class="product-card-img">
        ${badge}
        ${p.emoji}
      </div>
      <div class="product-card-body">
        <div class="product-name">${p.name}</div>
        <div class="product-brand">${p.brand} · ${p.category}</div>
        <div class="product-bottom">
          <div class="product-price">$${p.price.toFixed(2)}</div>
          <button class="product-add-btn ${inCart?'added':''}" onclick="event.stopPropagation();quickAdd(${p.id})">${inCart?'✓':'+'}</button>
        </div>
      </div>
    </div>`;
}

/* SEARCH */
let searchTimeout;
function handleSearch(val) {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    if (val.trim().length < 2) { document.getElementById('searchSuggestions').classList.remove('active'); return; }
    const q = val.toLowerCase();
    const results = PRODUCTS.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.brand.toLowerCase().includes(q) ||
      p.category.toLowerCase().includes(q) ||
      p.code.toLowerCase().includes(q)
    ).slice(0, 6);

    const box = document.getElementById('searchSuggestions');
    if (results.length === 0) { box.classList.remove('active'); return; }
    box.innerHTML = results.map(p => `
      <div class="suggestion-item" onmousedown="openModal(${p.id})">
        <div class="suggestion-thumb">${p.emoji}</div>
        <div class="suggestion-info">
          <div class="suggestion-name">${p.name}</div>
          <div class="suggestion-cat">${p.brand} · ${p.category}</div>
        </div>
        <div class="suggestion-price">$${p.price.toFixed(2)}</div>
      </div>`).join('');
    box.classList.add('active');
  }, 200);
}
function showSuggestions() { if(document.getElementById('mainSearch').value.length>=2) document.getElementById('searchSuggestions').classList.add('active'); }
function hideSuggestions() { setTimeout(()=>document.getElementById('searchSuggestions').classList.remove('active'), 200); }

/* PRODUCT MODAL */
function openModal(id) {
  const p = PRODUCTS.find(x => x.id === id);
  if (!p) return;
  currentModal = p;
  modalQty = 1;
  modalType = 'detal';

  document.getElementById('modalImg').innerHTML = `<span style="font-size:6rem">${p.emoji}</span>`;
  document.getElementById('modalBrand').textContent = p.brand;
  document.getElementById('modalName').textContent = p.name;
  document.getElementById('modalCat').textContent = p.category + ' · Código: ' + p.code;
  document.getElementById('modalPrice').textContent = '$' + p.price.toFixed(2);
  document.getElementById('modalPriceMayor').textContent = 'Mayor: $' + p.priceMayor.toFixed(2);
  document.getElementById('modalStock').querySelector('span').textContent = p.qty > p.minQty ? `${p.qty} unidades disponibles` : p.qty > 0 ? `Solo ${p.qty} unidades` : 'Sin stock';
  document.getElementById('modalStock').querySelector('.stock-dot').style.background = p.qty > p.minQty ? 'var(--c-green)' : p.qty > 0 ? 'var(--c-orange)' : '#EF4444';
  document.getElementById('modalQty').textContent = '1';

  document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
  document.querySelector('.type-btn').classList.add('active');

  document.getElementById('modalSpecs').innerHTML = `
    <div class="modal-specs-title">Especificaciones</div>
    <div class="spec-row"><span class="spec-key">Código</span><span class="spec-val">${p.code}</span></div>
    <div class="spec-row"><span class="spec-key">Marca</span><span class="spec-val">${p.brand}</span></div>
    <div class="spec-row"><span class="spec-key">Categoría</span><span class="spec-val">${p.category}</span></div>
    <div class="spec-row"><span class="spec-key">Precio detal</span><span class="spec-val">$${p.price.toFixed(2)}</span></div>
    <div class="spec-row"><span class="spec-key">Precio mayor</span><span class="spec-val">$${p.priceMayor.toFixed(2)}</span></div>
    <div class="spec-row"><span class="spec-key">Stock</span><span class="spec-val">${p.qty} uds</span></div>
  `;

  document.getElementById('productModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('productModal').classList.remove('active');
  document.body.style.overflow = '';
}

function changeQty(delta) {
  modalQty = Math.max(1, modalQty + delta);
  document.getElementById('modalQty').textContent = modalQty;
}

function selectType(type, el) {
  modalType = type;
  document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}

function addToCartFromModal() {
  if (!currentModal) return;
  addToCart(currentModal, modalQty, modalType);
  closeModal();
}

/* CART */
function quickAdd(id) {
  const p = PRODUCTS.find(x => x.id === id);
  addToCart(p, 1, 'detal');
}

function addToCart(product, qty, type) {
  const price = type === 'mayor' ? product.priceMayor : product.price;
  const existing = cart.find(c => c.id === product.id && c.type === type);
  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({ id: product.id, name: product.name, price, emoji: product.emoji, qty, type, brand: product.brand });
  }
  updateCartUI();
  showToast(`✅ ${product.name} agregado al carrito`);
  renderHomeProducts();
  if (document.getElementById('catalogSection').classList.contains('active')) renderCatalog();
}

function updateCartUI() {
  const total = cart.reduce((s,i) => s + i.price * i.qty, 0);
  const count = cart.reduce((s,i) => s + i.qty, 0);

  // Badge
  const badge = document.getElementById('cartBadge');
  badge.textContent = count;
  badge.classList.toggle('has-items', count > 0);

  // Show cart btn when has items
  document.getElementById('navCart').style.display = count > 0 ? 'flex' : 'none';
  document.getElementById('navCheckout').style.display = count > 0 ? 'flex' : 'none';

  // Total
  document.getElementById('cartTotal').textContent = '$' + total.toFixed(2);

  // Body
  const body = document.getElementById('cartBody');
  if (cart.length === 0) {
    body.innerHTML = `<div class="cart-empty"><div class="cart-empty-icon">🛒</div><div>Tu carrito está vacío</div><div style="font-size:.8rem">Agrega productos del catálogo</div></div>`;
    return;
  }
  body.innerHTML = cart.map((item, i) => `
    <div class="cart-item">
      <div class="cart-item-icon">${item.emoji}</div>
      <div class="cart-item-info">
        <div class="cart-item-name">${item.name}</div>
        <div class="cart-item-type">${item.type === 'mayor' ? '📦 Al mayor' : '🛍️ Al detal'}</div>
        <div class="cart-item-bottom">
          <div class="cart-item-price">$${(item.price * item.qty).toFixed(2)}</div>
          <div class="cart-item-qty">
            <button class="cart-qty-btn" onclick="cartQty(${i}, -1)">−</button>
            <span class="cart-qty-val">${item.qty}</span>
            <button class="cart-qty-btn" onclick="cartQty(${i}, 1)">+</button>
          </div>
        </div>
      </div>
    </div>`).join('');
}

function cartQty(idx, delta) {
  cart[idx].qty = Math.max(0, cart[idx].qty + delta);
  if (cart[idx].qty === 0) cart.splice(idx, 1);
  updateCartUI();
}

function toggleCart() {
  document.getElementById('cartSidebar').classList.toggle('open');
  document.getElementById('cartOverlay').classList.toggle('active');
  document.body.style.overflow = document.getElementById('cartSidebar').classList.contains('open') ? 'hidden' : '';
}

/* ORDER SUMMARY */
function renderOrderSummary() {
  const container = document.getElementById('orderSummaryItems');
  const total = cart.reduce((s,i) => s + i.price * i.qty, 0);
  container.innerHTML = cart.map(item => `
    <div class="order-summary-item">
      <span>${item.emoji} ${item.name} × ${item.qty}</span>
      <span>$${(item.price * item.qty).toFixed(2)}</span>
    </div>`).join('');
  document.getElementById('orderTotal').textContent = '$' + total.toFixed(2);
}

/* PAYMENT */
function selectPayment(method, el) {
  document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
  el.classList.add('selected');
  document.querySelectorAll('.payment-detail-box').forEach(b => b.classList.remove('active'));
  document.getElementById('pay-' + method).classList.add('active');
  document.getElementById('uploadSection').style.display = method === 'fisico' ? 'none' : 'block';
}

function handleFile(input) {
  const file = input.files[0];
  if (!file) return;
  const maxSize = 5 * 1024 * 1024;
  const allowed = ['image/png', 'image/jpeg', 'application/pdf'];
  if (!allowed.includes(file.type)) { showToast('⚠️ Formato no válido. Solo PNG, JPG o PDF', 'error'); input.value = ''; return; }
  if (file.size > maxSize) { showToast('⚠️ El archivo supera los 5 MB', 'error'); input.value = ''; return; }
  const area = document.querySelector('.upload-area');
  area.innerHTML = `<div class="upload-icon">✅</div><div class="upload-text">${file.name}</div><div class="upload-hint">${(file.size/1024).toFixed(0)} KB · ${file.type}</div>`;
  showToast('📎 Comprobante cargado correctamente');
}

function submitOrder() {
  if (cart.length === 0) { showToast('⚠️ Tu carrito está vacío', 'error'); return; }
  const nombre = document.getElementById('ckNombre').value.trim();
  const apellido = document.getElementById('ckApellido').value.trim();
  if (!nombre || !apellido) { showToast('⚠️ Completa los datos de entrega', 'error'); return; }
  cart = [];
  updateCartUI();
  navigate('confirmed');
}

/* AUTH (simulated) */
function doLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const pass = document.getElementById('loginPass').value;
  if (!email || !pass) { showToast('⚠️ Completa todos los campos', 'error'); return; }
  isLoggedIn = true;
  currentUser = { name: email.split('@')[0], email };
  // Update nav
  document.getElementById('navLogin').style.display = 'none';
  document.getElementById('navRegister').style.display = 'none';
  const logoutBtn = document.createElement('button');
  logoutBtn.className = 'nav-btn nav-btn-ghost';
  logoutBtn.textContent = '👤 ' + currentUser.name;
  logoutBtn.onclick = doLogout;
  logoutBtn.id = 'navUser';
  document.getElementById('navRegister').parentNode.insertBefore(logoutBtn, document.getElementById('navRegister'));
  showToast('✅ Bienvenido, ' + currentUser.name + '!');
  navigate('catalog');
}

function doRegister() {
  const nombre = document.getElementById('regNombre').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const pass = document.getElementById('regPass').value;
  if (!nombre || !email || !pass) { showToast('⚠️ Completa todos los campos requeridos', 'error'); return; }
  if (pass.length < 8) { showToast('⚠️ La contraseña debe tener al menos 8 caracteres', 'error'); return; }
  showToast('✅ Cuenta creada. Ahora inicia sesión.');
  navigate('login');
  document.getElementById('loginEmail').value = email;
}

function doLogout() {
  isLoggedIn = false; currentUser = null;
  document.getElementById('navLogin').style.display = '';
  document.getElementById('navRegister').style.display = '';
  const u = document.getElementById('navUser');
  if (u) u.remove();
  showToast('👋 Sesión cerrada');
  navigate('home');
}

/* TOAST */
function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = 'toast' + (type === 'error' ? ' error' : '');
  t.textContent = msg;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

/* THEME */
function toggleTheme() {
  const html = document.documentElement;
  const isDark = html.getAttribute('data-theme') === 'dark';
  html.setAttribute('data-theme', isDark ? 'light' : 'dark');
  document.querySelector('.theme-btn').textContent = isDark ? '🌙' : '☀️';
}

/* INIT */
document.addEventListener('DOMContentLoaded', () => {
  function boot() {
    renderHomeProducts();
    renderCatStrip();

    // Close modal on overlay click
    const pm = document.getElementById('productModal');
    if (pm) {
      pm.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
      });
    }

    // Keyboard ESC
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeModal();
    });
  }

  if (typeof PRODUCTS === 'undefined' || !Array.isArray(PRODUCTS) || PRODUCTS.length === 0) {
    fetch('/tienda/products.json')
      .then(r => r.json())
      .then(data => {
        window.PRODUCTS = data;
        BRANDS = [...new Set(PRODUCTS.map(p => p.brand))];
        boot();
      })
      .catch(err => {
        console.error('Could not load products', err);
        boot();
      });
  } else {
    BRANDS = [...new Set(PRODUCTS.map(p => p.brand))];
    boot();
  }
});
