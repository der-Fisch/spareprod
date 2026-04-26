@extends('layouts.app')

@section('title', 'Checkout | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Checkout</span>
    <h1>Pastikan alamat dan item checkout sudah sesuai.</h1>
    <p>Checkout saat ini disederhanakan ke satu alur pembayaran COD agar proses pemesanan lebih ringkas dan mudah dipahami.</p>
  </section>

  @if (! $user_can_continue)
    <div class="row">
      <div class="col-md-6">
        <div class="auth-card">
          <span class="eyebrow">Guest Checkout</span>
          <h2>Lanjut sebagai tamu</h2>
          <form method="POST" action="{{ route('checkout.guest') }}">
            @csrf
            <div class="form-group">
              <label for="guest-email">Email</label>
              <input id="guest-email" type="email" name="email" class="form-control" value="{{ old('email') }}" required>
              @foreach ($errors->guest->get('email') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
            </div>
            <div class="form-group">
              <label for="guest-email2">Konfirmasi Email</label>
              <input id="guest-email2" type="email" name="email2" class="form-control" value="{{ old('email2') }}" required>
              @foreach ($errors->guest->get('email2') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
            </div>
            <button type="submit" class="btn btn-primary btn-block">Lanjut sebagai Tamu</button>
          </form>
        </div>
      </div>

      <div class="col-md-6">
        <div class="auth-card">
          <span class="eyebrow">Masuk Akun</span>
          <h2>Login untuk melanjutkan</h2>
          <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="form-group">
              <label for="checkout-username">Username</label>
              <input id="checkout-username" type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="checkout-password">Password</label>
              <input id="checkout-password" type="password" name="password" class="form-control" required>
            </div>
            <input type="hidden" name="next" value="{{ $next_url }}">
            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
          </form>
          <div class="auth-links">
            <p>Belum punya akun? <a href="{{ route('register') }}">Daftar</a>.</p>
          </div>
        </div>
      </div>
    </div>
  @else
    @php
      $checkoutItems = $order->presentedItems();
    @endphp

    <form method="POST" action="{{ route('checkout.final') }}" class="checkout-shell" id="checkout-form">
      @csrf

      <div class="checkout-main">
        <div class="checkout-card">
          <div class="checkout-card-head">
            <div>
              <span class="eyebrow">Alamat Pengiriman</span>
              <h2>Kirim ke alamat yang tersedia</h2>
            </div>
            <a href="{{ route('checkout.address') }}" class="btn btn-outline">Ganti Alamat</a>
          </div>

          @if ($order->shippingAddress)
            <div class="checkout-address-card">
              <div class="checkout-address-copy">
                <strong>{{ $order->shippingAddress->display_label }}</strong>
                <p>{{ $order->shippingAddress->address }}</p>
              </div>
              @if ($order->shippingAddress->is_default)
                <span class="table-chip table-chip-success">Alamat utama</span>
              @endif
            </div>
          @else
            <div class="alert alert-warning">
              Alamat pengiriman belum dipilih. <a href="{{ route('checkout.address') }}">Pilih alamat dulu</a> sebelum menyelesaikan checkout.
            </div>
          @endif
        </div>

        <div class="checkout-card">
          <div class="checkout-card-head">
            <div>
              <span class="eyebrow">Barang Dipilih</span>
              <h2>Item yang akan di-checkout</h2>
            </div>
            <a href="{{ route('cart.index') }}" class="section-link">Back to Cart</a>
          </div>

          <div class="checkout-items">
            @foreach ($checkoutItems as $item)
              @php
                $productTitle = $item->product_title ?? $item->item?->product?->judul ?? 'Product';
                $productImage = $item->product_image_url ?? $item->item?->product?->image_url ?? asset('theme/img/marketing1.jpg');
              @endphp
              <article class="checkout-item-card">
                <div class="checkout-item-media">
                  <img src="{{ $productImage }}" alt="{{ $productTitle }}">
                </div>
                <div class="checkout-item-copy">
                  <h3>{{ $productTitle }}</h3>
                  <div class="checkout-item-meta">
                    <span class="account-badge">Qty {{ $item->quantity }}</span>
                    <span>{{ rupiah_catalog($item->line_item_total) }}</span>
                  </div>
                </div>
              </article>
            @endforeach
          </div>
        </div>
      </div>

      <aside class="checkout-sidebar">
        <div class="checkout-card checkout-sidebar-card">
          <div class="checkout-card-head checkout-card-head-stack">
            <div>
              <span class="eyebrow">Pembayaran</span>
              <h2>Metode pembayaran aktif</h2>
            </div>
            <p>Checkout menggunakan metode pembayaran COD agar proses pemesanan tetap cepat dan jelas.</p>
          </div>

          <div class="checkout-payment-mode-list">
            <div class="checkout-payment-mode is-active">
              <span class="checkout-payment-mode-copy">
                <strong>COD</strong>
                <small>Bayar saat barang diterima atau sesuai konfirmasi admin.</small>
              </span>
            </div>
          </div>

          <div class="checkout-divider"></div>

          <div class="checkout-summary-list">
            <div class="checkout-summary-row">
              <span>Total Harga ({{ $order->displayItemCount() }} barang)</span>
              <strong>{{ rupiah_catalog($order->displaySubtotal()) }}</strong>
            </div>
            <div class="checkout-summary-row">
              <span>Estimasi Pajak</span>
              <strong>{{ rupiah_catalog($order->displayTaxTotal()) }}</strong>
            </div>
            <div class="checkout-summary-row checkout-summary-row-total">
              <span>Total Tagihan</span>
              <strong>{{ rupiah_catalog($order->order_total) }}</strong>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block" id="checkout-submit-button">Buat Pesanan</button>

          <p class="checkout-helper-text">
            Pesanan akan dibuat lebih dulu, lalu pembayaran dilakukan saat pengiriman atau penerimaan.
          </p>
        </div>
      </aside>
    </form>
  @endif
@endsection

@push('scripts')
  @if ($user_can_continue)
    <script>
      (function ($) {
        var checkoutForm = $('#checkout-form');
        var submitButton = $('#checkout-submit-button');

        if (!checkoutForm.length) {
          return;
        }

        checkoutForm.on('submit', function (event) {
          if (checkoutForm.data('confirmed') === true) {
            submitButton.prop('disabled', true).text('Memproses...');
            return;
          }

          event.preventDefault();

          if (!window.Swal) {
            checkoutForm.data('confirmed', true);
            checkoutForm.trigger('submit');
            return;
          }

          window.Swal.fire({
            icon: 'question',
            title: 'Buat pesanan sekarang?',
            text: 'Pastikan alamat pengiriman dan item yang dipilih sudah benar.',
            showCancelButton: true,
            confirmButtonText: 'Ya, buat pesanan',
            cancelButtonText: 'Belum',
            reverseButtons: true,
            focusCancel: true
          }).then(function (result) {
            if (!result.isConfirmed) {
              return;
            }

            checkoutForm.data('confirmed', true);
            checkoutForm.trigger('submit');
          });
        });
      })(jQuery);
    </script>
  @endif
@endpush
