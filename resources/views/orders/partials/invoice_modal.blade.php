@php
  $invoiceModalId = $modalId ?? ('invoice-modal-' . $order->id);
  $invoiceAddress = trim((string) $order->shippingAddress?->address);
  $invoiceRecipient = trim((string) $order->shippingAddress?->recipient_name);
  $invoiceCustomerEmail = $order->user?->email ?: $order->accountUser?->email ?: '-';
  $invoiceDate = optional($order->tanggal_transaksi ?: $order->created_at)->format('d M Y, H:i');
@endphp

<div class="ui-modal" id="{{ $invoiceModalId }}">
  <div class="ui-modal-backdrop" data-ui-modal-close></div>
  <div class="ui-modal-dialog invoice-modal-dialog">
    <div class="ui-modal-content invoice-modal-content">
      <div class="ui-modal-header invoice-modal-header">
        <div>
          <span class="eyebrow">Invoice</span>
          <h3>Invoice #{{ $order->id_pembelian ?: $order->order_id ?: $order->id }}</h3>
        </div>
        <button type="button" class="ui-modal-close" data-ui-modal-close>&times;</button>
      </div>
      <div class="ui-modal-body invoice-modal-body">
        <div class="invoice-meta-grid">
          <div class="invoice-meta-card">
            <span>Order ID</span>
            <strong>{{ $order->order_id ?: $order->id }}</strong>
          </div>
          <div class="invoice-meta-card">
            <span>Tanggal</span>
            <strong>{{ $invoiceDate ?: '-' }}</strong>
          </div>
          <div class="invoice-meta-card">
            <span>Status</span>
            <strong>{{ $order->status_label }}</strong>
          </div>
          <div class="invoice-meta-card">
            <span>Pembayaran</span>
            <strong>{{ $order->payment_method_label }}</strong>
          </div>
        </div>

        <div class="invoice-party-grid">
          <div class="invoice-party-card">
            <span class="eyebrow">Diterbitkan Oleh</span>
            <h4>Spare Soko</h4>
            <p>support@sparesoko.co.id</p>
            <p>Jl. Ahmad Yani No. 88, Surabaya</p>
          </div>
          <div class="invoice-party-card">
            <span class="eyebrow">Ditujukan Kepada</span>
            <h4>{{ $invoiceCustomerEmail }}</h4>
            <p>
              {{ $invoiceAddress ?: '-' }}@if ($invoiceRecipient !== '')<br>(Atas Nama {{ $invoiceRecipient }})@endif
            </p>
          </div>
        </div>

        <div class="invoice-table-shell">
          <table class="table invoice-table">
            <thead>
              <tr>
                <th>Produk</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Harga</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($order->presentedItems() as $item)
                @php
                  $productTitle = $item->product_title ?? $item->item?->product?->title ?? 'Produk';
                  $variationTitle = $item->variation_title ?? $item->item?->title ?? null;
                @endphp
                <tr>
                  <td>
                    <strong>{{ $productTitle }}</strong>
                    @if ($variationTitle)
                      <div class="text-muted">{{ $variationTitle }}</div>
                    @endif
                  </td>
                  <td class="text-center">{{ $item->quantity }}</td>
                  <td class="text-right">{{ rupiah_catalog($item->line_item_total) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="invoice-total-card">
          <div class="invoice-total-row">
            <span>Subtotal</span>
            <strong>{{ rupiah_catalog($order->displaySubtotal()) }}</strong>
          </div>
          <div class="invoice-total-row">
            <span>Pajak</span>
            <strong>{{ rupiah_catalog($order->displayTaxTotal()) }}</strong>
          </div>
          <div class="invoice-total-row invoice-total-row-grand">
            <span>Total</span>
            <strong>{{ rupiah_catalog($order->order_total) }}</strong>
          </div>
        </div>

        <p class="invoice-note">Invoice ini merupakan bukti transaksi yang dapat digunakan sebagai referensi order.</p>

        <div class="ui-modal-actions">
          <button type="button" class="btn btn-ghost" data-ui-modal-close>Tutup</button>
        </div>
      </div>
    </div>
  </div>
</div>
