@extends('layouts.app')

@section('title', 'Tambah Alamat | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Alamat Pengiriman</span>
    <h1>Tambahkan alamat baru untuk checkout.</h1>
    <p>Isi label alamat, nama penerima, nomor WhatsApp, dan detail alamat agar checkout lebih cepat dan akurat.</p>
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
          <label for="nama_penerima">Nama penerima</label>
          <input id="nama_penerima" type="text" name="nama_penerima" class="form-control" value="{{ old('nama_penerima') }}" placeholder="Nama penerima paket" required>
          @error('nama_penerima')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="nomor_whatsapp">Nomor WhatsApp</label>
          <input id="nomor_whatsapp" type="text" name="nomor_whatsapp" class="form-control" value="{{ old('nomor_whatsapp') }}" placeholder="Contoh: 081234567890" required>
          @error('nomor_whatsapp')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="kode_pos">Kode pos</label>
          <input id="kode_pos" type="text" name="kode_pos" class="form-control" value="{{ old('kode_pos') }}" placeholder="Contoh: 17113" required>
          @error('kode_pos')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group form-group-span-2">
          <label for="nama_jalan">Jalan / detail alamat</label>
          <input id="nama_jalan" type="text" name="nama_jalan" class="form-control" value="{{ old('nama_jalan') }}" placeholder="Contoh: Jl. Melati No. 12, RT 03/RW 05" required>
          @error('nama_jalan')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="nama_kota">Kota / kabupaten</label>
          <input id="nama_kota" type="text" name="nama_kota" class="form-control" value="{{ old('nama_kota') }}" placeholder="Contoh: Kudus" required>
          @error('nama_kota')<p class="text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
          <label for="negara">Negara</label>
          <input id="negara" type="text" name="negara" class="form-control" value="{{ old('negara') }}" placeholder="Contoh: Indonesia" required>
          @error('negara')<p class="text-danger">{{ $message }}</p>@enderror
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
