<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin | Spare Soko')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link href="{{ asset('theme/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/css/navbar-static-top.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/css/custom.css') }}" rel="stylesheet">
  </head>
  <body class="backoffice-body">
    <div class="backoffice-shell">
      @include('partials.admin.sidebar')
      <button type="button" class="backoffice-sidebar-backdrop" data-backoffice-sidebar-close aria-label="Close sidebar"></button>
      <div class="backoffice-main">
        @include('partials.admin.topbar')
        <main class="backoffice-content">
          <div class="backoffice-flash-stack">
            @foreach (['success', 'info', 'error'] as $flashType)
              @if (session($flashType))
                <div class="alert alert-{{ $flashType === 'error' ? 'danger' : $flashType }} alert-dismissible flash-banner" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  {{ session($flashType) }}
                </div>
              @endif
            @endforeach
          </div>
          @yield('content')
        </main>
      </div>
    </div>
    <div id="backoffice-modal"></div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('theme/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('theme/js/ie10-viewport-bug-workaround.js') }}"></script>
    <script src="{{ asset('theme/js/custom.js') }}"></script>
    <script src="{{ asset('theme/js/layout-shell.js') }}"></script>
    <script src="{{ asset('theme/js/admin-form-widgets.js') }}"></script>
    <script src="{{ asset('theme/js/admin-entity-page.js') }}"></script>
    @stack('scripts')
  </body>
</html>
