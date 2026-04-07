<aside class="backoffice-sidebar">
  <div class="backoffice-sidebar-header">
    <a class="brand-lockup" href="{{ route('home') }}">
      <span class="brand-icon"><span>SS</span></span>
      <span class="brand-copy">
        <strong>Spare Soko</strong>
        <small>Industrial Auto Parts</small>
      </span>
    </a>
    <p class="backoffice-sidebar-subtitle">Admin workspace untuk katalog, user, dan order.</p>
  </div>
  <nav class="backoffice-nav">
    <a href="{{ route('backoffice.dashboard') }}" class="backoffice-nav-link{{ request()->routeIs('backoffice.dashboard') ? ' is-active' : '' }}">
      <i class="fa fa-dashboard"></i>
      <span>Dashboard</span>
    </a>
    <a href="{{ route('backoffice.entity.list', ['entity' => 'categories']) }}" class="backoffice-nav-link{{ request()->is('backoffice/categories*') ? ' is-active' : '' }}">
      <i class="fa fa-tags"></i>
      <span>Categories</span>
    </a>
    <a href="{{ route('backoffice.entity.list', ['entity' => 'products']) }}" class="backoffice-nav-link{{ request()->is('backoffice/products*') ? ' is-active' : '' }}">
      <i class="fa fa-cubes"></i>
      <span>Products</span>
    </a>
    <a href="{{ route('backoffice.entity.list', ['entity' => 'users']) }}" class="backoffice-nav-link{{ request()->is('backoffice/users*') ? ' is-active' : '' }}">
      <i class="fa fa-users"></i>
      <span>Users</span>
    </a>
    <a href="{{ route('backoffice.entity.list', ['entity' => 'orders']) }}" class="backoffice-nav-link{{ request()->is('backoffice/orders*') ? ' is-active' : '' }}">
      <i class="fa fa-shopping-cart"></i>
      <span>Orders</span>
    </a>
  </nav>
</aside>
