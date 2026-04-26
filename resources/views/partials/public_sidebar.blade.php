<aside class="public-sidebar" id="public-sidebar">
  <div class="public-sidebar-header">
    <a class="brand-lockup" href="{{ route('home') }}">
      <span class="brand-icon">
        <span>SS</span>
      </span>
      <span class="brand-copy">
        <strong>Spare Soko</strong>
        <small>Industrial Auto Parts</small>
      </span>
    </a>
  </div>
  <nav class="public-sidebar-nav">
    <a class="public-sidebar-link{{ request()->routeIs('home') ? ' is-active' : '' }}" href="{{ route('home') }}">
      <i class="fa fa-home"></i>
      <span>Home</span>
    </a>
    <a class="public-sidebar-link{{ request()->routeIs('products.*') ? ' is-active' : '' }}" href="{{ route('products.index') }}">
      <i class="fa fa-cubes"></i>
      <span>Products</span>
    </a>
    <a class="public-sidebar-link{{ request()->routeIs('orders.*') ? ' is-active' : '' }}" href="{{ route('orders.index') }}">
      <i class="fa fa-shopping-cart"></i>
      <span>Orders</span>
    </a>
    <a class="public-sidebar-link{{ request()->routeIs('account.settings*') ? ' is-active' : '' }}" href="{{ route('account.settings') }}">
      <i class="fa fa-user-circle-o"></i>
      <span>Settings</span>
    </a>
    @if (auth()->user()?->is_staff)
      <a class="public-sidebar-link{{ request()->routeIs('backoffice.*') ? ' is-active' : '' }}" href="{{ route('backoffice.dashboard') }}">
        <i class="fa fa-dashboard"></i>
        <span>Panel Admin</span>
      </a>
    @endif
  </nav>
</aside>
