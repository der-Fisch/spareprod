@extends('layouts.app')

@section('title', 'Pilih Alamat | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Checkout</span>
    <h1>Pilih alamat pengiriman untuk pesanan ini.</h1>
    <p>Alamat dipilih lebih dulu, lalu pesanan COD dikonfirmasi di halaman checkout.</p>
  </section>

  <div class="address-selection-shell">
    <div class="checkout-card">
      <div class="checkout-card-head">
        <div>
          <span class="eyebrow">Daftar Alamat</span>
          <h2>Pilih satu alamat aktif</h2>
        </div>
        <a class="btn btn-outline" href="{{ route('checkout.address.create') }}">Tambah Alamat Baru</a>
      </div>

      <form method="POST" action="{{ route('checkout.address.store') }}" class="address-selection-form">
        @csrf

        <div class="address-selection-list">
          @foreach ($shippingAddresses as $address)
            <label class="address-option-card @if(old('shipping_address', $order->shipping_address_id) == $address->id) is-active @endif">
              <input type="radio" name="shipping_address" value="{{ $address->id }}" @checked(old('shipping_address', $order->shipping_address_id) == $address->id)>
              <span class="address-option-copy">
                <span class="address-option-topline">
                  <strong>{{ $address->display_label }}</strong>
                  @if ($address->is_default)
                    <span class="table-chip table-chip-success">Utama</span>
                  @endif
                </span>
                <span>{{ $address->recipient_name }}</span>
                <span>{{ $address->phone_number }}</span>
                <span>{{ $address->address }}</span>
              </span>
            </label>
          @endforeach
        </div>

        @error('shipping_address')<p class="text-danger">{{ $message }}</p>@enderror

        <div class="address-selection-actions">
          <button class="btn btn-primary" type="submit">Simpan dan Lanjutkan</button>
          <a class="btn btn-ghost" href="{{ route('checkout') }}">Kembali ke checkout</a>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    (function ($) {
      function syncSelectedAddressCard() {
        $('.address-option-card').removeClass('is-active');
        $('input[name="shipping_address"]:checked').closest('.address-option-card').addClass('is-active');
      }

      $(document).on('change', 'input[name="shipping_address"]', syncSelectedAddressCard);
      syncSelectedAddressCard();
    })(jQuery);
  </script>
@endpush
