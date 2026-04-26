<span class="eyebrow">Ringkasan Pesanan</span>
<h2>Tinjau detail order</h2>
<table class="table summary-table">
  <tr>
    <td>
      {{ $order->displayItemCount() }} barang: <br>
      @foreach ($order->presentedItems() as $item)
        @php
          $productTitle = $item->product_title ?? $item->item?->product?->title ?? 'Produk';
        @endphp
        <b>{{ $productTitle }}</b>
        x {{ $item->quantity }}Pcs : {{ rupiah_catalog($item->line_item_total) }}<br>
      @endforeach
    </td>
  </tr>
  <tr><td>Subtotal: {{ rupiah_catalog($order->displaySubtotal()) }}</td></tr>
  <tr><td>Pajak: {{ rupiah_catalog($order->displayTaxTotal()) }}</td></tr>
  <tr><td>Total barang: {{ rupiah_catalog($order->displayItemsTotal()) }}</td></tr>
  <tr><td>Total pembayaran: {{ rupiah_catalog($order->order_total) }}</td></tr>
  <tr><td>Metode Pembayaran: {{ $order->payment_method_label }}</td></tr>
</table>
@if ($order->shippingAddress)
  @php
    $addressText = trim((string) $order->shippingAddress->address);
    $recipientName = trim((string) $order->shippingAddress->recipient_name);
    $addressSuffix = $recipientName !== '' ? ' (Atas Nama ' . $recipientName . ')' : '';
  @endphp
  <p>
    <b>Alamat :</b> {{ $addressText }}{{ $addressSuffix }}
  </p>
@endif
