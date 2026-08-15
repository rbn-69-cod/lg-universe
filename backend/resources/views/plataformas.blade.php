{{-- resources/views/plataformas.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>IG UNIVERSE • Catálogo Premium</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --bg-dark: #0a0a0f;
      --bg-card: rgba(12, 12, 20, 0.65);
      --glass-border: rgba(255, 255, 255, 0.08);
      --glass-highlight: rgba(255, 255, 255, 0.03);
      --neon-cyan: #00f3ff;
      --neon-pink: #ff2d9e;
      --neon-purple: #b100ff;
      --grad-primary: linear-gradient(135deg, #00f3ff, #7000ff);
      --grad-hot: linear-gradient(135deg, #ff2d9e, #f09819);
      --shadow-neon: 0 0 15px rgba(0, 243, 255, 0.3), 0 8px 32px rgba(0, 0, 0, 0.4);
      --shadow-card: 0 20px 40px rgba(0, 0, 0, 0.5);
      --transition-bounce: cubic-bezier(0.34, 1.2, 0.64, 1);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: radial-gradient(ellipse at 20% 30%, #0f0c1f, #05050a);
      color: #fff;
      min-height: 100vh;
      overflow-x: hidden;
      padding-bottom: 100px;
    }

    /* Fondo animado con partículas */
    .orb {
      position: fixed;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 0;
    }
    .orb::before {
      content: "";
      position: absolute;
      width: 60vmax;
      height: 60vmax;
      background: radial-gradient(circle, rgba(0, 243, 255, 0.15), transparent 70%);
      top: -20%;
      left: -20%;
      animation: float1 25s infinite alternate;
    }
    .orb::after {
      content: "";
      position: absolute;
      width: 50vmax;
      height: 50vmax;
      background: radial-gradient(circle, rgba(255, 45, 158, 0.12), transparent 70%);
      bottom: -10%;
      right: -10%;
      animation: float2 30s infinite alternate;
    }
    @keyframes float1 {
      0% { transform: translate(0, 0) scale(1); opacity: 0.6; }
      100% { transform: translate(10%, 15%) scale(1.3); opacity: 0.3; }
    }
    @keyframes float2 {
      0% { transform: translate(0, 0) scale(1); opacity: 0.5; }
      100% { transform: translate(-12%, -8%) scale(1.4); opacity: 0.2; }
    }

    /* Header neón */
    .header {
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(20px);
      background: rgba(5, 5, 10, 0.75);
      border-bottom: 1px solid rgba(0, 243, 255, 0.3);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }
    .header-inner {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0.8rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .logo-area {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .logo-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(145deg, #00f3ff, #7000ff);
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 20px rgba(0, 243, 255, 0.6);
    }
    .logo-icon i {
      font-size: 1.8rem;
      color: white;
    }
    .logo-text {
      font-family: 'Space Grotesk', monospace;
      font-weight: 800;
      font-size: 1.7rem;
      letter-spacing: -1px;
      background: linear-gradient(135deg, #fff, #00f3ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .badge-header {
      background: rgba(0, 243, 255, 0.15);
      backdrop-filter: blur(8px);
      border-radius: 60px;
      padding: 0.5rem 1rem;
      font-size: 0.8rem;
      border: 1px solid rgba(0, 243, 255, 0.4);
    }
    .cart-top {
      background: var(--grad-hot);
      border: none;
      border-radius: 60px;
      padding: 0.5rem 1.2rem;
      color: white;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      transition: all 0.2s var(--transition-bounce);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    .cart-top:hover {
      transform: scale(1.03);
      filter: brightness(1.1);
    }

    /* Barra de búsqueda */
    .top-bar {
      max-width: 1400px;
      margin: 2rem auto 1.5rem;
      padding: 0 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .search-wrapper {
      flex: 1;
      max-width: 500px;
      position: relative;
    }
    .search-wrapper i {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #00f3ff;
      font-size: 1rem;
    }
    .search-input {
      width: 100%;
      background: rgba(20, 20, 35, 0.7);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(0, 243, 255, 0.3);
      border-radius: 60px;
      padding: 0.8rem 1rem 0.8rem 2.8rem;
      color: white;
      font-size: 0.9rem;
      transition: all 0.2s;
    }
    .search-input:focus {
      outline: none;
      border-color: #00f3ff;
      box-shadow: 0 0 12px rgba(0, 243, 255, 0.5);
    }
    .stats {
      background: rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(8px);
      padding: 0.5rem 1rem;
      border-radius: 60px;
      font-size: 0.85rem;
    }

    /* Grid de tarjetas - diseño PRO */
    .grid {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 2rem;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
      gap: 2rem;
    }
    .card {
      background: var(--bg-card);
      backdrop-filter: blur(16px);
      border-radius: 2rem;
      border: 1px solid var(--glass-border);
      padding: 1.5rem;
      transition: all 0.3s var(--transition-bounce);
      transform: translateY(20px);
      opacity: 0;
      animation: fadeUp 0.5s forwards;
      animation-delay: calc(var(--i, 0) * 0.08s);
      box-shadow: var(--shadow-card);
      position: relative;
      overflow: hidden;
    }
    .card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(125deg, rgba(255,255,255,0.02) 0%, rgba(255,255,255,0) 80%);
      pointer-events: none;
    }
    .card:hover {
      transform: translateY(-12px) scale(1.01);
      border-color: rgba(0, 243, 255, 0.6);
      box-shadow: 0 25px 45px rgba(0, 0, 0, 0.6), 0 0 30px rgba(0, 243, 255, 0.2);
    }
    @keyframes fadeUp {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    /* Imagen grande y moderna */
    .card-img {
      width: 110px;
      height: 110px;
      border-radius: 28px;
      background: rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(0, 243, 255, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      transition: 0.2s;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    }
    .card-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .card-img i {
      font-size: 3rem;
      color: #00f3ff;
    }
    .card-header {
      display: flex;
      gap: 1rem;
      align-items: center;
      margin-bottom: 1rem;
    }
    .card-info {
      flex: 1;
    }
    .card-title {
      font-family: 'Space Grotesk', monospace;
      font-weight: 700;
      font-size: 1.5rem;
      letter-spacing: -0.5px;
      background: linear-gradient(135deg, #fff, #aaffff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .premium-tag {
      display: inline-block;
      background: rgba(0, 243, 255, 0.2);
      border-radius: 40px;
      padding: 0.2rem 0.8rem;
      font-size: 0.7rem;
      font-weight: 600;
      border: 1px solid #00f3ff;
      margin-top: 6px;
    }
    .price {
      text-align: right;
      font-family: 'Space Grotesk', monospace;
      font-weight: 800;
      font-size: 1.7rem;
      color: #fff;
      text-shadow: 0 0 6px #00f3ff;
    }
    .price small {
      font-size: 0.7rem;
      font-weight: 400;
      color: #ccc;
    }

    /* Features - elegantes */
    .features {
      margin: 1.2rem 0;
      background: rgba(0, 0, 0, 0.3);
      border-radius: 1.2rem;
      padding: 0.8rem;
    }
    .feature {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 0.8rem;
      padding: 0.4rem 0;
      color: #e0e0ff;
    }
    .feature i {
      color: #ff2d9e;
      width: 20px;
      font-size: 0.8rem;
    }

    /* Botones con efecto neón */
    .actions {
      display: flex;
      gap: 1rem;
      margin-top: 0.5rem;
    }
    .btn {
      flex: 1;
      border: none;
      border-radius: 60px;
      padding: 0.8rem 0;
      font-weight: 800;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      cursor: pointer;
      transition: 0.2s;
      font-family: 'Inter', sans-serif;
      letter-spacing: 0.5px;
    }
    .btn-add {
      background: var(--grad-hot);
      color: white;
      box-shadow: 0 4px 12px rgba(255, 45, 158, 0.4);
    }
    .btn-buy {
      background: rgba(0, 0, 0, 0.5);
      border: 1px solid #00f3ff;
      color: #00f3ff;
    }
    .btn:hover {
      transform: translateY(-3px);
      filter: brightness(1.08);
    }

    /* Carrito flotante y modal mejorado */
    .cart-float {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      z-index: 200;
    }
    .cart-btn {
      background: var(--grad-hot);
      border: none;
      border-radius: 80px;
      padding: 1rem 1.6rem;
      font-weight: bold;
      color: white;
      display: flex;
      align-items: center;
      gap: 0.8rem;
      font-size: 1rem;
      cursor: pointer;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
      transition: 0.2s;
    }
    .cart-btn:hover {
      transform: scale(1.05);
    }
    .badge {
      background: white;
      color: black;
      border-radius: 60px;
      padding: 0.2rem 0.6rem;
      font-weight: 800;
    }

    /* Modal (igual pero más pulido) */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(16px);
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: 0.2s;
    }
    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .modal-container {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.96);
      width: min(550px, 90%);
      background: rgba(10, 10, 20, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 2rem;
      border: 1px solid rgba(0, 243, 255, 0.4);
      z-index: 1001;
      opacity: 0;
      visibility: hidden;
      transition: 0.2s;
    }
    .modal-container.active {
      opacity: 1;
      visibility: visible;
      transform: translate(-50%, -50%) scale(1);
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .modal-close {
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
    }
    .modal-body {
      max-height: 60vh;
      overflow-y: auto;
      padding: 1rem;
    }
    .cart-item {
      background: rgba(0,0,0,0.4);
      border-radius: 1.2rem;
      padding: 0.8rem;
      margin-bottom: 0.8rem;
    }
    .item-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .qty-control {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }
    .qty-btn {
      width: 30px;
      height: 30px;
      border-radius: 40px;
      background: #00f3ff20;
      border: 1px solid #00f3ff;
      color: white;
      cursor: pointer;
    }
    .remove-btn {
      margin-top: 8px;
      background: rgba(255, 45, 158, 0.2);
      border: 1px solid #ff2d9e;
      border-radius: 40px;
      padding: 0.3rem;
      width: 100%;
      color: white;
      cursor: pointer;
    }
    .modal-footer {
      padding: 1rem;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    .pay-btn {
      width: 100%;
      background: var(--grad-hot);
      border: none;
      border-radius: 60px;
      padding: 0.9rem;
      font-weight: bold;
      color: white;
      margin-top: 1rem;
      cursor: pointer;
    }
    .toast {
      position: fixed;
      bottom: 100px;
      left: 50%;
      transform: translateX(-50%);
      background: #000000cc;
      backdrop-filter: blur(12px);
      border-radius: 60px;
      padding: 0.6rem 1.2rem;
      color: white;
      font-size: 0.85rem;
      z-index: 2000;
      opacity: 0;
      transition: 0.2s;
      pointer-events: none;
    }
    .toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(-8px);
    }
    @media (max-width: 640px) {
      .header-inner, .top-bar { padding: 0 1rem; }
      .grid { padding: 0 1rem; gap: 1rem; }
      .card-img { width: 80px; height: 80px; }
      .card-title { font-size: 1.2rem; }
      .price { font-size: 1.3rem; }
    }
  </style>
</head>
<body>
<div class="orb"></div>

<header class="header">
  <div class="header-inner">
    <div class="logo-area">
      <div class="logo-icon"><i class="fas fa-globe-americas"></i></div>
      <span class="logo-text">IG UNIVERSE</span>
    </div>
    <div style="display: flex; gap: 1rem; align-items: center;">
      <div class="badge-header"><i class="fas fa-tv"></i> <span id="platformCount">0</span> plataformas</div>
      <button class="cart-top" id="cartTopBtn"><i class="fas fa-shopping-cart"></i> CARRITO <span id="cartCountTop">0</span></button>
    </div>
  </div>
</header>

<div class="top-bar">
  <div class="search-wrapper">
    <i class="fas fa-search"></i>
    <input type="text" id="searchInput" class="search-input" placeholder="Buscar servicio...">
  </div>
  <div class="stats"><i class="fas fa-cart-shopping"></i> En carrito: <strong id="cartCountBadge">0</strong></div>
</div>

<div class="grid" id="catalogGrid">
  @if(isset($plataformas) && $plataformas->count())
    @foreach($plataformas as $idx => $plataforma)
      @php
        // Procesar features
        $featuresList = [];
        $raw = $plataforma->features ?? null;
        if($raw) {
          if(is_string($raw)) {
            $decoded = json_decode($raw, true);
            if(json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $featuresList = $decoded;
            else $featuresList = array_map('trim', preg_split('/[\n\r,]+/', $raw));
          } elseif(is_array($raw)) $featuresList = $raw;
        }
        if(empty($featuresList)) $featuresList = ['✓ Streaming 4K Ultra HD', '✓ Catálogo ilimitado', '✓ 3 dispositivos simultáneos'];
        $imgUrl = $plataforma->imagen;
        $hasImg = !empty($imgUrl) && filter_var($imgUrl, FILTER_VALIDATE_URL);
      @endphp
      <div class="card" style="--i: {{ $idx }}" data-id="{{ $plataforma->id }}" data-name="{{ $plataforma->nombre }}" data-price="{{ $plataforma->precio }}" data-image="{{ $imgUrl }}">
        <div class="card-header">
          <div class="card-img">
            @if($hasImg)
              <img src="{{ $imgUrl }}" alt="{{ $plataforma->nombre }}" onerror="this.onerror=null; this.src='https://placehold.co/200x200/1a1a2e/00f3ff?text={{ urlencode(substr($plataforma->nombre,0,1)) }}'">
            @else
              <i class="fas fa-film"></i>
            @endif
          </div>
          <div class="card-info">
            <div class="card-title">{{ $plataforma->nombre }}</div>
            <div class="premium-tag"><i class="fas fa-crown"></i> PREMIUM ACCESS</div>
          </div>
          <div class="price">S/ {{ number_format($plataforma->precio, 2) }}<small>/mes</small></div>
        </div>

        @if(count($featuresList))
          <div class="features">
            @foreach($featuresList as $feat)
              <div class="feature"><i class="fas fa-bolt"></i> {{ $feat }}</div>
            @endforeach
          </div>
        @endif

        <div class="actions">
          <button class="btn btn-add add-to-cart"><i class="fas fa-cart-plus"></i> AGREGAR</button>
          <button class="btn btn-buy buy-now"><i class="fas fa-bolt"></i> PAGAR AHORA</button>
        </div>
      </div>
    @endforeach
  @else
    <div class="card" style="text-align:center; grid-column:1/-1;">✨ No hay plataformas cargadas aún ✨</div>
  @endif
</div>

<div class="cart-float">
  <button class="cart-btn" id="cartFloatBtn"><i class="fas fa-shopping-cart"></i> CARRITO <span class="badge" id="cartFloatBadge">0</span></button>
</div>

<div class="modal-overlay" id="modalOverlay"></div>
<div class="modal-container" id="modalCart">
  <div class="modal-header">
    <h3><i class="fas fa-terminal"></i> Tu pedido</h3>
    <button class="modal-close" id="closeModalBtn">✕</button>
  </div>
  <div class="modal-body" id="cartItemsList"></div>
  <div class="modal-footer">
    <div style="display: flex; justify-content: space-between;"><span>SUBTOTAL</span> <strong id="cartSubtotal">S/ 0.00</strong></div>
    <div style="display: flex; justify-content: space-between; margin-top: 8px;"><span>TOTAL</span> <strong id="cartTotal">S/ 0.00</strong></div>
    <button class="pay-btn" id="checkoutBtn"><i class="fab fa-whatsapp"></i> CONTINUAR A PAGO</button>
    <div style="font-size: 0.7rem; text-align: center; margin-top: 12px;">🔒 Seguro · Envío automático a WhatsApp</div>
  </div>
</div>

<div class="toast" id="toastMsg"><i class="fas fa-check-circle"></i> <span id="toastText"></span></div>

<script>
  (function() {
    const CART_KEY = 'ig_cart_pro';
    let cart = [];

    // DOM elements
    const platformCountSpan = document.getElementById('platformCount');
    const cartCountTop = document.getElementById('cartCountTop');
    const cartCountBadge = document.getElementById('cartCountBadge');
    const cartFloatBadge = document.getElementById('cartFloatBadge');
    const searchInput = document.getElementById('searchInput');
    const catalog = document.getElementById('catalogGrid');
    const modalOverlay = document.getElementById('modalOverlay');
    const modalCart = document.getElementById('modalCart');
    const cartItemsList = document.getElementById('cartItemsList');
    const cartSubtotalSpan = document.getElementById('cartSubtotal');
    const cartTotalSpan = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const toastMsg = document.getElementById('toastMsg');
    const toastTextSpan = document.getElementById('toastText');

    function showToast(msg) {
      toastTextSpan.innerText = msg;
      toastMsg.classList.add('show');
      setTimeout(() => toastMsg.classList.remove('show'), 2000);
    }

    function saveCart() { localStorage.setItem(CART_KEY, JSON.stringify(cart)); }
    function loadCart() { try { cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); } catch(e) { cart = []; } updateCartUI(); }

    function updateCartUI() {
      let totalItems = cart.reduce((sum, i) => sum + i.quantity, 0);
      cartCountTop.innerText = totalItems;
      cartCountBadge.innerText = totalItems;
      cartFloatBadge.innerText = totalItems;

      let subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
      cartSubtotalSpan.innerText = `S/ ${subtotal.toFixed(2)}`;
      cartTotalSpan.innerText = `S/ ${subtotal.toFixed(2)}`;
      renderCartItems();
    }

    function renderCartItems() {
      if(!cart.length) {
        cartItemsList.innerHTML = '<div style="text-align:center; padding:2rem;">🛒 El carrito está vacío</div>';
        return;
      }
      cartItemsList.innerHTML = cart.map((item, idx) => `
        <div class="cart-item" data-index="${idx}">
          <div class="item-row">
            <strong>${escapeHtml(item.name)}</strong>
            <span>S/ ${(item.price * item.quantity).toFixed(2)}</span>
          </div>
          <div class="item-row" style="margin-top: 8px;">
            <div class="qty-control">
              <button class="qty-btn" data-action="dec">-</button>
              <span>${item.quantity}</span>
              <button class="qty-btn" data-action="inc">+</button>
            </div>
            <button class="remove-btn" data-action="remove">Eliminar</button>
          </div>
        </div>
      `).join('');
    }

    function addToCart(product) {
      let exist = cart.find(i => i.id == product.id);
      if(exist) exist.quantity += 1;
      else cart.push({ id: product.id, name: product.name, price: parseFloat(product.price), quantity: 1 });
      saveCart();
      updateCartUI();
      showToast(`➕ ${product.name} agregado`);
    }

    function updateQuantity(index, delta) {
      let newQ = cart[index].quantity + delta;
      if(newQ <= 0) cart.splice(index,1);
      else cart[index].quantity = newQ;
      saveCart();
      updateCartUI();
    }

    function removeItem(index) {
      cart.splice(index,1);
      saveCart();
      updateCartUI();
      showToast('🗑️ Eliminado');
    }

    function openModal() { modalOverlay.classList.add('active'); modalCart.classList.add('active'); document.body.style.overflow = 'hidden'; }
    function closeModal() { modalOverlay.classList.remove('active'); modalCart.classList.remove('active'); document.body.style.overflow = ''; }

    function goToCheckout() {
      if(!cart.length) { showToast('❌ Agrega algo al carrito'); return; }
      const total = cart.reduce((s,i) => s + (i.price * i.quantity), 0);
      const payload = { items: cart, total, timestamp: new Date().toISOString(), currency: 'PEN' };
      sessionStorage.setItem('checkout_payload', JSON.stringify(payload));
      window.location.href = '/pago';
    }

    function escapeHtml(str) { return String(str).replace(/[&<>]/g, function(m){ if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

    // Search filter
    function filterPlatforms() {
      let term = searchInput.value.toLowerCase();
      let visible = 0;
      document.querySelectorAll('.card[data-name]').forEach(card => {
        let name = card.getAttribute('data-name').toLowerCase();
        if(name.includes(term)) { card.style.display = ''; visible++; }
        else card.style.display = 'none';
      });
      platformCountSpan.innerText = visible;
    }
    searchInput.addEventListener('input', filterPlatforms);

    // Delegation for add/buy
    catalog.addEventListener('click', (e) => {
      const card = e.target.closest('.card');
      if(!card) return;
      const id = card.getAttribute('data-id');
      const name = card.getAttribute('data-name');
      const price = card.getAttribute('data-price');
      if(e.target.closest('.add-to-cart')) addToCart({ id, name, price });
      if(e.target.closest('.buy-now')) { addToCart({ id, name, price }); goToCheckout(); }
    });

    // Modal events
    document.getElementById('cartTopBtn').addEventListener('click', openModal);
    document.getElementById('cartFloatBtn').addEventListener('click', openModal);
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);
    checkoutBtn.addEventListener('click', goToCheckout);

    // Cart item actions
    cartItemsList.addEventListener('click', (e) => {
      const itemDiv = e.target.closest('.cart-item');
      if(!itemDiv) return;
      const idx = parseInt(itemDiv.getAttribute('data-index'));
      const action = e.target.closest('[data-action]')?.getAttribute('data-action');
      if(action === 'inc') updateQuantity(idx, 1);
      if(action === 'dec') updateQuantity(idx, -1);
      if(action === 'remove') removeItem(idx);
    });

    // initial count
    function updatePlatformCount() {
      let total = document.querySelectorAll('.card[data-name]').length;
      platformCountSpan.innerText = total;
    }
    loadCart();
    updatePlatformCount();
    filterPlatforms(); // for initial visible count
  })();
</script>
</body>
</html>