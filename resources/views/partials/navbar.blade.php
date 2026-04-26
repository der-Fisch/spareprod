<header class="site-navbar" data-navbar-hide>
  <div class="site-navbar-inner">
    <div class="site-navbar-section site-navbar-left">
      @auth
        <button type="button" class="sidebar-toggle-button" data-sidebar-toggle aria-label="Toggle sidebar">
          <span></span>
          <span></span>
          <span></span>
        </button>
      @else
        <a class="brand-lockup" href="{{ route('home') }}">
          <span class="brand-icon">
            <span>SS</span>
          </span>
          <span class="brand-copy">
            <strong>Spare Soko</strong>
            <small>Industrial Auto Parts</small>
          </span>
        </a>
      @endauth
    </div>

    <div class="site-navbar-section site-navbar-center">
      @if (auth()->check() && request()->routeIs('products.index'))
        <form class="nav-search-form" method="GET" action="{{ route('products.index') }}">
          <i class="fa fa-search"></i>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari produk, kategori, atau deskripsi sparepart">
        </form>
      @elseif (auth()->check())
        <span></span>
      @elseif (request()->routeIs('login') || request()->routeIs('register'))
        <span></span>
      @elseif (request()->routeIs('home'))
        <nav class="scroll-nav">
          <a href="#hero" class="scroll-nav-link" data-scroll-target="hero">Beranda</a>
          <a href="#why-choose-us" class="scroll-nav-link" data-scroll-target="why-choose-us">Why Choose Us</a>
          <a href="#catalog-preview" class="scroll-nav-link" data-scroll-target="catalog-preview">Preview</a>
          <a href="#vision-mission" class="scroll-nav-link" data-scroll-target="vision-mission">Vision & Mission</a>
          <a href="#contact-cta" class="scroll-nav-link" data-scroll-target="contact-cta">Contact</a>
        </nav>
      @else
        <a class="brand-lockup" href="{{ route('home') }}">
          <span class="brand-icon">
            <span>SS</span>
          </span>
          <span class="brand-copy">
            <strong>Spare Soko</strong>
            <small>Industrial Auto Parts</small>
          </span>
        </a>
      @endif
    </div>

    <div class="site-navbar-section site-navbar-right">
      @auth
        <a class="nav-cart-pill" href="{{ route('cart.index') }}">
          <i class="fa fa-shopping-cart"></i>
          <span class="nav-cart-count" id="cart-count-badge" data-cart-count>{{ session('cart_item_count', $sharedCartCount) }}</span>
        </a>
        <ul class="nav navbar-nav navbar-right nav-user-shell">
          <li class="dropdown nav-user-dropdown">
            <a href="#" class="dropdown-toggle nav-user-trigger" data-toggle="dropdown" role="button" aria-expanded="false">
              <span class="nav-user-avatar">{{ avatar_initials(auth()->user()) }}</span>
              <span class="nav-user-copy">
                <strong>{{ auth()->user()->username }}</strong>
                <small>{{ auth()->user()->is_staff ? 'Area Admin' : 'Sudah masuk' }}</small>
              </span>
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
              @unless (auth()->user()->is_staff)
                <li><a href="{{ route('account.settings') }}">Settings</a></li>
                <li><a href="{{ route('orders.index') }}">Orders</a></li>
              @endunless
              <li class="divider"></li>
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="btn btn-link">Log out</button>
                </form>
              </li>
            </ul>
          </li>
        </ul>
      @else
        <div class="nav-auth-actions">
          <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
          <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
        </div>
      @endauth
    </div>
  </div>
</header>

