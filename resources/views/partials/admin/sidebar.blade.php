<aside class="admin-sidebar">
  <div class="admin-sidebar-header">
    <a class="brand-lockup" href="{{ route('home') }}">
      <span class="brand-icon"><span>SS</span></span>
      <span class="brand-copy">
        <strong>Spare Soko</strong>
        <small>Industrial Auto Parts</small>
      </span>
    </a>
    <p class="admin-sidebar-subtitle">Panel admin untuk katalog dan order.</p>
  </div>
  <nav class="admin-nav">
    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link{{ request()->routeIs('admin.dashboard') ? ' is-active' : '' }}">
      <i class="fa fa-dashboard"></i>
      <span>Dashboard</span>
    </a>
    <a href="{{ route('admin.entity.list', ['entity' => 'categories']) }}" class="admin-nav-link{{ request()->is('admin/categories*') ? ' is-active' : '' }}">
      <i class="fa fa-tags"></i>
      <span>Kategori</span>
    </a>
    <a href="{{ route('admin.entity.list', ['entity' => 'products']) }}" class="admin-nav-link{{ request()->is('admin/products*') ? ' is-active' : '' }}">
      <i class="fa fa-cubes"></i>
      <span>Products</span>
    </a>
    <a href="{{ route('admin.entity.list', ['entity' => 'orders']) }}" class="admin-nav-link{{ request()->is('admin/orders*') ? ' is-active' : '' }}">
      <i class="fa fa-shopping-cart"></i>
      <span>Orders</span>
    </a>
  </nav>
</aside>

