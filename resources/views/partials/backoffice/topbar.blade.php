<header class="backoffice-topbar">
  <button type="button" class="sidebar-toggle-button" data-backoffice-sidebar-toggle aria-label="Toggle admin sidebar">
    <span></span>
    <span></span>
    <span></span>
  </button>
  <div class="backoffice-topbar-user dropdown">
    <a href="#" class="dropdown-toggle nav-user-trigger" data-toggle="dropdown" role="button" aria-expanded="false">
      <span class="nav-user-avatar">{{ avatar_initials(auth()->user()) }}</span>
      <span class="nav-user-copy">
        <strong>{{ auth()->user()->username }}</strong>
        <small>Admin Workspace</small>
      </span>
      <span class="caret"></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-right">
      <li><a href="{{ route('account.settings') }}">Settings</a></li>
      <li class="divider"></li>
      <li>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn btn-link">Log out</button>
        </form>
      </li>
    </ul>
  </div>
</header>
