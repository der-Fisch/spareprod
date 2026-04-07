@extends('layouts.app')

@section('title', 'Tambah Alamat | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Alamat Pengiriman</span>
    <h1>Tambahkan alamat baru untuk checkout.</h1>
    <p>Isi label alamat, nama penerima, nomor HP, dan detail alamat agar checkout lebih mudah dipahami user.</p>
  </section>

  <div class="address-form-shell">
    <div class="checkout-card">
      <div class="checkout-card-head checkout-card-head-stack">
        <div>
          <span class="eyebrow">Form Alamat</span>
          <h2>Alamat baru</h2>
        </div>
        <p>Alamat ini akan masuk ke daftar alamat user dan bisa dipakai lagi pada checkout berikutnya.</p>
      </div>

      <form method="POST" action="{{ route('checkout.address.save') }}" class="address-form-grid">
        @csrf

        <div class="form-group">
          <label for="label">Label alamat</label>
          <input id="label" type="text" name="label" class="form-control" value="{{ old('label') }}" placeholder="Contoh: Rumah, Kantor, Gudang">
          @error('label')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="recipient_name">Nama penerima</label>
          <input id="recipient_name" type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name') }}" placeholder="Nama penerima paket" required>
          @error('recipient_name')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="phone_number">Nomor HP</label>
          <input id="phone_number" type="text" name="phone_number" class="form-control" value="{{ old('phone_number') }}" placeholder="Contoh: 081234567890" required>
          @error('phone_number')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="zipcode">Kode pos</label>
          <input id="zipcode" type="text" name="zipcode" class="form-control" value="{{ old('zipcode') }}" placeholder="Contoh: 17113" required>
          @error('zipcode')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group form-group-span-2">
          <label for="street">Jalan / detail alamat</label>
          <input id="street" type="text" name="street" class="form-control" value="{{ old('street') }}" placeholder="Contoh: Jl. Melati No. 12, RT 03/RW 05" required>
          @error('street')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="city">Kota / kabupaten</label>
          <input id="city" type="text" name="city" class="form-control" value="{{ old('city') }}" placeholder="Contoh: Kudus" required>
          @error('city')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="state">Provinsi</label>
          <input id="state" type="text" name="state" class="form-control" value="{{ old('state') }}" placeholder="Contoh: Jawa Tengah" required>
          @error('state')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group form-group-span-2">
          <label class="checkbox-inline">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))>
            Jadikan alamat utama untuk checkout berikutnya
          </label>
        </div>

        <div class="account-form-actions">
          <button class="btn btn-primary" type="submit">Simpan Alamat</button>
          <a class="btn btn-ghost" href="{{ route('checkout.address') }}">Kembali ke daftar alamat</a>
        </div>
      </form>
    </div>
  </div>
@endsection
