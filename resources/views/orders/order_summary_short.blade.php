<span class="eyebrow">Ringkasan Pesanan</span>
<h2>Tinjau detail order</h2>
<table class="table summary-table">
  <tr>
    <td>
      {{ $order->displayItemCount() }} barang: <br>
      @foreach ($order->presentedItems() as $item)
        @php
          $productTitle = $item->product_title ?? $item->item?->product?->title ?? 'Produk';
          $variationTitle = $item->variation_title ?? $item->item?->title ?? null;
        @endphp
        <b>{{ $productTitle }}</b>
        @if ($variationTitle)
          <span class="text-muted">({{ $variationTitle }})</span>
        @endif
        x{{ $item->quantity }} - {{ rupiah_catalog($item->line_item_total) }}<br>
      @endforeach
    </td>
  </tr>
  <tr><td>Subtotal: {{ rupiah_catalog($order->displaySubtotal()) }}</td></tr>
  <tr><td>Pajak: {{ rupiah_catalog($order->displayTaxTotal()) }}</td></tr>
  <tr><td>Total barang: {{ rupiah_catalog($order->displayItemsTotal()) }}</td></tr>
  <tr><td>Biaya kirim: {{ rupiah_catalog($order->shipping_total_price) }}</td></tr>
  <tr><td>Total order: {{ rupiah_catalog($order->order_total) }}</td></tr>
  <tr><td>Metode Pembayaran: {{ $order->payment_method_label }}</td></tr>
</table>
@if ($order->shippingAddress)
  @php
    $shippingAddressName = trim(collect([
      $order->shippingAddress->display_label,
      $order->shippingAddress->recipient_name,
    ])->filter()->implode(' - '));
  @endphp
  <p>
    <b>Alamat :</b> {{ $order->shippingAddress->address }}
  </p>
@endif
