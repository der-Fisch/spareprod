@extends('layouts.app')

@section('title', 'Account Settings | Spare Soko')

@php
  $genderOptions = [
    'male' => 'Laki-laki',
    'female' => 'Perempuan',
    'other' => 'Lainnya',
  ];
@endphp

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Pusat Akun</span>
    <h1>Kelola biodata, alamat, dan keamanan akun Anda.</h1>
    <p>Pastikan data inti akun Anda selalu lengkap agar proses belanja, checkout, dan order berjalan lebih lancar.</p>
  </section>

  <section class="section-shell account-shell">
    <div class="account-hub">
      <div class="account-hub-hero">
        <div class="account-hub-identity">
          <div class="account-hub-avatar">{{ avatar_initials(auth()->user()) }}</div>
          <div class="account-hub-copy">
            <span class="eyebrow">Akun Pelanggan</span>
            <h2>{{ auth()->user()->name }}</h2>
            <p>{{ auth()->user()->email }}</p>
            <small>Member sejak {{ optional(auth()->user()->date_joined)->format('F Y') ?: 'tanggal bergabung belum tercatat' }}</small>
          </div>
        </div>

        <div class="account-hub-metrics">
          <div class="account-hub-metric">
            <span>Alamat</span>
            <strong>{{ $addresses->count() }}</strong>
          </div>
          <div class="account-hub-metric">
            <span>Email Checkout</span>
            <strong>{{ $checkoutProfile->email ?: auth()->user()->email }}</strong>
          </div>
          <div class="account-hub-metric">
            <span>HP Utama</span>
            <strong>{{ $profile->phone_number ?: 'Belum diisi' }}</strong>
          </div>
        </div>
      </div>

      <div class="account-hub-card">
        <div class="account-hub-tabs">
          <a href="{{ route('account.settings', ['tab' => 'biodata']) }}" class="account-hub-tab{{ $activeTab === 'biodata' ? ' is-active' : '' }}">Biodata Diri</a>
          <a href="{{ route('account.settings', ['tab' => 'addresses']) }}" class="account-hub-tab{{ $activeTab === 'addresses' ? ' is-active' : '' }}">Daftar Alamat</a>
          <a href="{{ route('account.settings', ['tab' => 'security']) }}" class="account-hub-tab{{ $activeTab === 'security' ? ' is-active' : '' }}">Keamanan</a>
        </div>

        <div class="account-hub-body">
          @if ($activeTab === 'biodata')
            <div class="account-grid account-grid-biodata">
              <div class="account-panel-card">
                <div class="account-panel-head">
                  <div>
                    <span class="eyebrow">Biodata</span>
                    <h3>Informasi dasar akun</h3>
                  </div>
                  <p>Data ini digunakan sebagai profil utama akun dan tersinkron ke proses checkout.</p>
                </div>

                <form method="POST" action="{{ route('account.settings.update') }}" class="account-form-grid">
                  @csrf
                  <input type="hidden" name="action" value="profile">
                  <input type="hidden" name="active_tab" value="biodata">

                  <div class="form-group">
                    <label for="first_name">Nama Depan</label>
                    <input id="first_name" type="text" name="first_name" class="form-control" value="{{ old('first_name', auth()->user()->first_name) }}">
                    @foreach ($errors->getBag('profile')->get('first_name') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="form-group">
                    <label for="last_name">Nama Belakang</label>
                    <input id="last_name" type="text" name="last_name" class="form-control" value="{{ old('last_name', auth()->user()->last_name) }}">
                    @foreach ($errors->getBag('profile')->get('last_name') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="form-group">
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" class="form-control" value="{{ old('username', auth()->user()->username) }}" required>
                    @foreach ($errors->getBag('profile')->get('username') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
                    @foreach ($errors->getBag('profile')->get('email') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="form-group">
                    <label for="phone_number">Nomor HP</label>
                    <input id="phone_number" type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $profile->phone_number) }}" placeholder="Contoh: 08123456789">
                    @foreach ($errors->getBag('profile')->get('phone_number') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="form-group">
                    <label for="whatsapp_number">Nomor WhatsApp</label>
                    <input id="whatsapp_number" type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $profile->whatsapp_number) }}" placeholder="Contoh: 08123456789">
                    @foreach ($errors->getBag('profile')->get('whatsapp_number') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="form-group">
                    <label for="birth_date">Tanggal Lahir</label>
                    <input id="birth_date" type="date" name="birth_date" class="form-control" value="{{ old('birth_date', optional($profile->birth_date)->format('Y-m-d')) }}">
                    @foreach ($errors->getBag('profile')->get('birth_date') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="form-group">
                    <label for="gender">Jenis Kelamin</label>
                    <select id="gender" name="gender" class="form-control">
                      <option value="">Pilih jenis kelamin</option>
                      @foreach ($genderOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $profile->gender) === $value)>{{ $label }}</option>
                      @endforeach
                    </select>
                    @foreach ($errors->getBag('profile')->get('gender') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
                  </div>

                  <div class="account-form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Biodata</button>
                  </div>
                </form>
              </div>

              <div class="account-stack">
                <div class="account-panel-card account-summary-panel">
                  <span class="eyebrow">Ringkasan Akun</span>
                  <h3>Snapshot data customer</h3>
                  <div class="account-summary-list">
                    <div class="account-summary-item">
                      <span>Nama Lengkap</span>
                      <strong>{{ auth()->user()->name ?: 'Belum diisi' }}</strong>
                    </div>
                    <div class="account-summary-item">
                      <span>Nomor HP</span>
                      <strong>{{ $profile->phone_number ?: 'Belum diisi' }}</strong>
                    </div>
                    <div class="account-summary-item">
                      <span>WhatsApp</span>
                      <strong>{{ $profile->whatsapp_number ?: 'Belum diisi' }}</strong>
                    </div>
                    <div class="account-summary-item">
                      <span>Tanggal Lahir</span>
                      <strong>{{ optional($profile->birth_date)->format('d M Y') ?: 'Belum diisi' }}</strong>
                    </div>
                    <div class="account-summary-item">
                      <span>Jenis Kelamin</span>
                      <strong>{{ $genderOptions[$profile->gender] ?? 'Belum diisi' }}</strong>
                    </div>
                  </div>
                </div>

                <div class="account-panel-card account-summary-panel">
                  <span class="eyebrow">Sinkronisasi</span>
                  <h3>Terhubung ke checkout</h3>
                  <p>Email dan alamat akun digunakan kembali saat checkout agar proses belanja lebih cepat dan konsisten.</p>
                </div>
              </div>
            </div>
          @endif

          @if ($activeTab === 'addresses')
            <div class="account-panel-card">
              <div class="account-panel-head">
                <div>
                  <span class="eyebrow">Daftar Alamat</span>
                  <h3>Alamat pengiriman akun</h3>
                </div>
                <div class="account-head-actions">
                  <span class="account-mini-note">Alamat default akan diprioritaskan di checkout.</span>
                  <button type="button" class="btn btn-primary" data-ui-modal-open="address-create-modal">Tambah Alamat Baru</button>
                </div>
              </div>

              @if ($addresses->isEmpty())
                <div class="account-empty-state">
                  <h4>Belum ada alamat tersimpan.</h4>
                  <p>Tambahkan alamat utama user di sini agar nanti checkout bisa langsung memilih alamat yang sudah siap digunakan.</p>
                </div>
              @else
                <div class="account-card-grid">
                  @foreach ($addresses as $address)
                    <div class="account-data-card{{ $address->is_default ? ' is-highlighted' : '' }}">
                      <div class="account-data-card-head">
                        <div>
                          <h4>{{ $address->display_label }}</h4>
                          <p>{{ $address->recipient_name ?: auth()->user()->name }}</p>
                        </div>
                        <div class="account-badges">
                          @if ($address->is_default)
                            <span class="account-badge account-badge-success">Utama</span>
                          @endif
                          <span class="account-badge">{{ strtoupper($address->type) }}</span>
                        </div>
                      </div>

                      <div class="account-data-card-body">
                        <p><strong>No. HP</strong><br>{{ $address->phone_number ?: 'Belum diisi' }}</p>
                        <p><strong>Alamat</strong><br>{{ $address->address }}</p>
                      </div>

                      <div class="account-data-card-actions">
                        @if (! $address->is_default)
                          <form method="POST" action="{{ route('account.settings.update') }}">
                            @csrf
                            <input type="hidden" name="action" value="address_default">
                            <input type="hidden" name="active_tab" value="addresses">
                            <input type="hidden" name="address_id" value="{{ $address->id }}">
                            <button type="submit" class="btn btn-link">Jadikan Utama</button>
                          </form>
                        @endif
                        <button type="button" class="btn btn-link" data-ui-modal-open="address-edit-modal-{{ $address->id }}">Ubah Alamat</button>
                        <form method="POST" action="{{ route('account.settings.update') }}" onsubmit="return confirm('Hapus alamat ini?');">
                          @csrf
                          <input type="hidden" name="action" value="address_delete">
                          <input type="hidden" name="active_tab" value="addresses">
                          <input type="hidden" name="address_id" value="{{ $address->id }}">
                          <button type="submit" class="btn btn-link text-danger">Hapus</button>
                        </form>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          @endif

          @if ($activeTab === 'security')
            <div class="account-grid account-grid-security">
              <div class="account-panel-card">
                <div class="account-panel-head">
                  <div>
                    <span class="eyebrow">Keamanan</span>
                    <h3>Ganti password akun</h3>
                  </div>
                  <p>Bagian ini tetap dipisahkan agar pengelolaan profil dan keamanan tidak bercampur.</p>
                </div>

                <form method="POST" action="{{ route('account.settings.update') }}" class="account-form-grid account-form-grid-compact">
                  @csrf
                  <input type="hidden" name="action" value="password">
                  <input type="hidden" name="active_tab" value="security">

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

                  <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required>
                  </div>

                  <div class="account-form-actions">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                  </div>
                </form>
              </div>

              <div class="account-panel-card account-summary-panel">
                <span class="eyebrow">Catatan</span>
                <h3>Praktik aman</h3>
                <ul class="account-security-list">
                  <li>Gunakan password minimal 8 karakter.</li>
                  <li>Pisahkan password akun dengan akses admin.</li>
                  <li>Aktifkan pemeriksaan keamanan tambahan bila akun dipakai di lebih dari satu perangkat.</li>
                </ul>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </section>

  @include('account.partials.address_modal', [
    'modalId' => 'address-create-modal',
    'title' => 'Tambah Alamat Baru',
    'submitLabel' => 'Simpan Alamat',
    'actionValue' => 'address_create',
    'activeTab' => 'addresses',
    'activeModal' => $activeModal,
    'errorBag' => $errors->getBag('address_create'),
    'address' => null,
  ])

  @foreach ($addresses as $address)
    @include('account.partials.address_modal', [
      'modalId' => 'address-edit-modal-' . $address->id,
      'title' => 'Ubah Alamat',
      'submitLabel' => 'Simpan Perubahan',
      'actionValue' => 'address_update',
      'activeTab' => 'addresses',
      'activeModal' => $activeModal,
      'errorBag' => $errors->getBag('address_update_' . $address->id),
      'address' => $address,
    ])
  @endforeach
@endsection
