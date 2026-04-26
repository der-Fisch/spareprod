@extends('layouts.app')

@section('title', 'Order Detail | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Order Detail</span>
    <h1>Order #{{ $order->order_id ?: $order->id }}</h1>
    <p>Tinjau ringkasan transaksi dan alamat pengiriman pada halaman ini.</p>
  </section>

  <div class="col-md-8 col-md-offset-2">
    <div class="summary-card">
      <div class="order-status-banner">
        <span>Status</span>
        <strong>{{ $order->status_label }}</strong>
      </div>
      <div class="alert alert-info">
        Pesanan COD sudah dibuat. Simpan detail order ini sebagai acuan pengiriman dan pembayaran saat barang diterima.
      </div>
      @include('orders.order_summary_short', ['order' => $order])
      <p><b>Simpan halaman ini sebagai referensi order Anda.</b></p>
      <div class="order-detail-actions text-center" style="margin-top: 22px;">
        <button type="button" class="btn btn-ghost" data-ui-modal-open="invoice-modal-order-detail-{{ $order->id }}">Invoice</button>
        <a href="{{ route('products.index') }}" class="btn btn-primary">Kembali Belanja</a>
      </div>
    </div>
  </div>

  @include('orders.partials.invoice_modal', [
    'order' => $order,
    'modalId' => 'invoice-modal-order-detail-' . $order->id,
  ])
@endsection

@push('scripts')
  @if (session('checkout_success'))
    <script>
      (function () {
        if (!window.Swal) {
          return;
        }

        window.Swal.fire({
          icon: 'success',
          title: 'Pesanan berhasil dibuat',
          text: @json(session('checkout_success')),
          confirmButtonText: 'Oke'
        });
      })();
    </script>
  @endif
@endpush

