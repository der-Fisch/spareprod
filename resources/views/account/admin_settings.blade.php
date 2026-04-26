@extends('admin.base')

@section('title', 'Admin Settings | Spare Soko')

@section('content')
  <section class="backoffice-hero">
    <div>
      <span class="eyebrow">Admin Settings</span>
      <h1>Kelola identitas login admin.</h1>
      <p>Halaman ini disederhanakan untuk kebutuhan operasional admin: username, email, WhatsApp, dan password.</p>
    </div>
    <div class="backoffice-hero-icon">
      <i class="fa fa-user-secret"></i>
    </div>
  </section>

  <section class="backoffice-dashboard-grid">
    <div class="backoffice-dashboard-main">
      <div class="backoffice-panel">
        <div class="panel-heading-inline">
          <div>
            <span class="eyebrow">Profil Admin</span>
            <h2>Informasi akun inti</h2>
          </div>
        </div>

        <form method="POST" action="{{ route('account.settings.update') }}" class="account-form-grid account-form-grid-compact">
          @csrf
          <input type="hidden" name="action" value="profile">

          <div class="form-group">
            <label for="admin_username">Username</label>
            <input id="admin_username" type="text" name="username" class="form-control" value="{{ old('username', auth()->user()->username) }}" required>
            @foreach ($errors->getBag('profile')->get('username') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="admin_email">Email</label>
            <input id="admin_email" type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
            @foreach ($errors->getBag('profile')->get('email') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group form-group-span-2">
            <label for="admin_whatsapp_number">Nomor WhatsApp</label>
            <input id="admin_whatsapp_number" type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $profile->whatsapp_number) }}" placeholder="Contoh: 08123456789">
            @foreach ($errors->getBag('profile')->get('whatsapp_number') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="account-form-actions">
            <button type="submit" class="btn btn-primary">Simpan Profil Admin</button>
          </div>
        </form>
      </div>

      <div class="backoffice-panel">
        <div class="panel-heading-inline">
          <div>
            <span class="eyebrow">Keamanan</span>
            <h2>Ganti password admin</h2>
          </div>
        </div>

        <form method="POST" action="{{ route('account.settings.update') }}" class="account-form-grid account-form-grid-compact">
          @csrf
          <input type="hidden" name="action" value="password">

          <div class="form-group">
            <label for="current_password">Password Lama</label>
            <input id="current_password" type="password" name="current_password" class="form-control" required>
            @foreach ($errors->getBag('password')->get('current_password') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="password">Password Baru</label>
            <input id="password" type="password" name="password" class="form-control" required>
            @foreach ($errors->getBag('password')->get('password') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group form-group-span-2">
            <label for="password_confirmation">Konfirmasi Password Baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
          </div>

          <div class="account-form-actions">
            <button type="submit" class="btn btn-primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>

    <div class="backoffice-panel quick-actions-panel">
      <span class="eyebrow">Ringkasan</span>
      <h2>Akun Admin</h2>
      <p class="panel-helper">Data yang tampil di sini hanya yang relevan untuk operasional admin.</p>
      <div class="summary-mini-card">
        <span>Username</span>
        <strong>{{ auth()->user()->username }}</strong>
      </div>
      <div class="summary-mini-card">
        <span>Email</span>
        <strong>{{ auth()->user()->email }}</strong>
      </div>
      <div class="summary-mini-card">
        <span>WhatsApp</span>
        <strong>{{ $profile->whatsapp_number ?: 'Belum diisi' }}</strong>
      </div>
    </div>
  </section>
@endsection
