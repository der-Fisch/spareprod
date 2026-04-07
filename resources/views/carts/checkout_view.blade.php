@extends('layouts.app')

@section('title', 'Checkout | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Checkout</span>
    <h1>Pastikan alamat, item, dan metode pembayaran sudah sesuai.</h1>
    <p>Halaman checkout sekarang menampilkan alamat aktif, item yang dipilih dari keranjang, dan opsi pembayaran dalam satu tampilan yang lebih jelas.</p>
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
      $selectedPaymentMethodId = (int) old('user_payment_method_id', $order->user_payment_method_id);
      $selectedPaymentMode = old('payment_method', $order->payment_method ?: ($payment_methods->isNotEmpty() ? 'prepaid' : 'cod'));
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
            <a href="{{ route('cart') }}" class="section-link">Kembali ke keranjang</a>
          </div>

          <div class="checkout-items">
            @foreach ($checkoutItems as $item)
              @php
                $productTitle = $item->product_title ?? $item->item?->product?->title ?? 'Produk';
                $variationTitle = $item->variation_title ?? $item->item?->title ?? null;
                $productImage = $item->product_image_url ?? $item->item?->product?->image_url ?? asset('theme/img/marketing1.jpg');
              @endphp
              <article class="checkout-item-card">
                <div class="checkout-item-media">
                  <img src="{{ $productImage }}" alt="{{ $productTitle }}">
                </div>
                <div class="checkout-item-copy">
                  <h3>{{ $productTitle }}</h3>
                  @if ($variationTitle)
                    <p>{{ $variationTitle }}</p>
                  @endif
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
              <h2>Pilih cara bayar</h2>
            </div>
            <p>Pilih COD atau metode pembayaran yang sudah tersimpan di akun Anda.</p>
          </div>

          <div class="checkout-payment-mode-list">
            <label class="checkout-payment-mode @if($selectedPaymentMode === 'prepaid') is-active @endif">
              <input type="radio" name="payment_method" value="prepaid" @checked($selectedPaymentMode === 'prepaid') @disabled($payment_methods->isEmpty())>
              <span class="checkout-payment-mode-copy">
                <strong>Bayar Sekarang</strong>
                <small>Pilih metode pembayaran yang sudah tersimpan di akun.</small>
              </span>
            </label>

            <div class="checkout-payment-methods @if($payment_methods->isEmpty()) is-empty @endif">
              @forelse ($payment_methods as $paymentMethod)
                <label class="checkout-payment-method @if($selectedPaymentMethodId === $paymentMethod->id && $selectedPaymentMode === 'prepaid') is-active @endif">
                  <input type="radio" name="user_payment_method_id" value="{{ $paymentMethod->id }}" @checked($selectedPaymentMethodId === $paymentMethod->id)>
                  <span class="checkout-payment-method-copy">
                    <strong>{{ $paymentMethod->provider_name }}</strong>
                    <small>{{ strtoupper($paymentMethod->method_type) }} • {{ $paymentMethod->masked_reference }}</small>
                  </span>
                  <span class="table-chip {{ $paymentMethod->status === 'connected' ? 'table-chip-success' : 'table-chip-warning' }}">{{ $paymentMethod->status_label }}</span>
                </label>
              @empty
                <div class="checkout-payment-empty">
                  <p>Metode pembayaran prepaid belum diatur.</p>
                  @auth
                    <a href="{{ route('account.settings', ['tab' => 'payments']) }}" class="btn btn-outline btn-sm">Atur di Settings</a>
                  @else
                    <p class="checkout-helper-text">Login dulu untuk mengatur metode pembayaran non-COD.</p>
                  @endauth
                </div>
              @endforelse
            </div>

            <label class="checkout-payment-mode @if($selectedPaymentMode === 'cod') is-active @endif">
              <input type="radio" name="payment_method" value="cod" @checked($selectedPaymentMode === 'cod')>
              <span class="checkout-payment-mode-copy">
                <strong>COD</strong>
                <small>Bayar saat barang diterima atau sesuai konfirmasi admin.</small>
              </span>
            </label>
          </div>

          @error('payment_method')<p class="text-danger">{{ $message }}</p>@enderror
          @error('user_payment_method_id')<p class="text-danger">{{ $message }}</p>@enderror

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

          <button type="submit" class="btn btn-primary btn-block" id="checkout-submit-button">
            {{ $selectedPaymentMode === 'cod' ? 'Buat Pesanan COD' : 'Bayar Sekarang' }}
          </button>

          <p class="checkout-helper-text" id="checkout-submit-help">
            {{ $selectedPaymentMode === 'cod'
              ? 'Pesanan akan dibuat lebih dulu, lalu pembayaran dilakukan saat pengiriman atau penerimaan.'
              : 'Pembayaran akan diproses menggunakan metode yang Anda pilih.' }}
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
        function syncCheckoutPaymentUi() {
          var selectedMode = $('input[name="payment_method"]:checked').val() || 'cod';
          var selectedMethod = $('input[name="user_payment_method_id"]:checked').val();

          $('.checkout-payment-mode').removeClass('is-active');
          $('input[name="payment_method"]:checked').closest('.checkout-payment-mode').addClass('is-active');

          $('.checkout-payment-method').removeClass('is-active');
          if (selectedMethod && selectedMode === 'prepaid') {
            $('input[name="user_payment_method_id"]:checked').closest('.checkout-payment-method').addClass('is-active');
          }

          if (selectedMode === 'cod') {
            $('#checkout-submit-button').text('Buat Pesanan COD');
            $('#checkout-submit-help').text('Pesanan akan dibuat lebih dulu, lalu pembayaran dilakukan saat pengiriman atau penerimaan.');
          } else {
            $('#checkout-submit-button').text('Bayar Sekarang');
            $('#checkout-submit-help').text('Pembayaran akan diproses menggunakan metode yang Anda pilih.');
          }
        }

        $(document).on('change', 'input[name="payment_method"]', syncCheckoutPaymentUi);
        $(document).on('change', 'input[name="user_payment_method_id"]', function () {
          $('input[name="payment_method"][value="prepaid"]').prop('checked', true);
          syncCheckoutPaymentUi();
        });

        syncCheckoutPaymentUi();
      })(jQuery);
    </script>
  @endif
@endpush
