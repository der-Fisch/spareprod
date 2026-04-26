@extends('layouts.app')

@section('title', 'Shopping Cart | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Cart</span>
    <h1>Kelola item yang ingin dibeli sebelum lanjut checkout.</h1>
    <p>Pilih semua, pilih per produk, ubah jumlah, dan lanjutkan checkout hanya dengan item yang benar-benar Anda centang.</p>
  </section>

  <div class="cart-shell">
    <template id="cart-empty-state-template">{!! view('carts.empty_cart')->render() !!}</template>
    @if ($object->cartItems->count() < 1)
      @include('carts.empty_cart')
    @else
      @php
        $groupedItems = $object->cartItems->groupBy(fn ($item) => $item->item->product->brand_name ?: 'Product Lainnya');
      @endphp

      <div class="cart-main" id="cart-main-content">
        <div class="cart-toolbar-card">
          <label class="cart-selector-label">
            <input type="checkbox" id="cart-select-all" @checked($object->all_items_selected)>
            <span>Pilih Semua ({{ $object->cartItems->count() }})</span>
          </label>

          <form id="remove-selected-form" method="POST" action="{{ route('cart.items.remove_selected') }}">
            @csrf
            <button type="submit" class="btn btn-link{{ $object->selected_item_count < 1 ? ' disabled' : '' }}" @disabled($object->selected_item_count < 1)>Hapus</button>
          </form>
        </div>

        @foreach ($groupedItems as $brandName => $items)
          <div class="cart-brand-card" data-brand-card>
            <div class="cart-brand-head">
              <label class="cart-selector-label">
                <input
                  type="checkbox"
                  class="cart-brand-selector"
                  data-cart-item-ids="{{ $items->pluck('id')->implode(',') }}"
                  @checked($items->every(fn ($item) => $item->is_selected))
                >
                <span>{{ $brandName }}</span>
              </label>
            </div>

            @foreach ($items as $item)
              <div class="cart-item-card" id="cart-item-{{ $item->id }}" data-cart-item-row>
                <div class="cart-item-selector-shell">
                  <input
                    type="checkbox"
                    class="cart-item-selector"
                    data-cart-item-id="{{ $item->id }}"
                    @checked($item->is_selected)
                  >
                </div>

                <div class="cart-item-media">
                  <img src="{{ $item->product_image_url }}" alt="{{ $item->item->product->judul }}">
                </div>

                <div class="cart-item-copy">
                  <h3>{{ $item->item->product->judul }}</h3>
                  <div class="cart-item-meta">
                    <span class="account-badge">{{ $item->item->product->brand_name ?: 'Spare Part' }}</span>
                    <span class="cart-unit-price">{{ rupiah_catalog($item->quantity > 0 ? ($item->line_item_total / $item->quantity) : 0) }} / item</span>
                  </div>
                  @if ($item->stock_issue_message)
                    <p class="cart-stock-warning">{{ $item->stock_issue_message }}</p>
                  @endif
                </div>

                <div class="cart-item-side">
                  <strong id="item-line-total-{{ $item->id }}">{{ rupiah_catalog($item->line_item_total) }}</strong>
                  <div class="cart-item-actions">
                    <button
                      type="button"
                      class="cart-remove-link cart-remove-button"
                      data-cart-item-delete
                      data-delete-action="{{ route('cart.items.destroy', $item) }}"
                      data-cart-item-id="{{ $item->id }}"
                    >
                      Hapus
                    </button>
                    <form action="{{ route('cart.items.update', $item) }}" method="POST" class="cart-qty-form" data-cart-qty-form>
                      @csrf
                      @method('PATCH')
                      <input type="number" class="item-qty form-control" data-cart-item-id="{{ $item->id }}" name="quantity" value="{{ $item->quantity }}" min="0">
                    </form>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>

      <aside class="cart-summary-panel">
        <div class="cart-summary-card">
          <span class="eyebrow">Ringkasan belanja</span>
          <h2>Belanja terpilih</h2>

          <div class="cart-summary-row">
            <span>Item dipilih</span>
            <strong id="cart-selected-count">{{ $object->selected_item_count }}</strong>
          </div>
          <div class="cart-summary-row">
            <span>Subtotal</span>
            <strong id="cart-selected-subtotal">{{ rupiah_catalog($object->selected_subtotal) }}</strong>
          </div>
          <div class="cart-summary-row">
            <span>Pajak</span>
            <strong id="cart-selected-tax">{{ rupiah_catalog($object->total_pajak_terpilih) }}</strong>
          </div>
          <div class="cart-summary-row cart-summary-row-total">
            <span>Total</span>
            <strong id="cart-selected-total">{{ rupiah_catalog($object->selected_total) }}</strong>
          </div>

          <form method="GET" action="{{ route('checkout') }}">
            <button id="cart-checkout-button" type="submit" class="btn btn-primary btn-block" @disabled($object->selected_item_count < 1)>
              Beli ({{ $object->selected_item_count }})
            </button>
          </form>

          <p class="cart-summary-helper">Hanya item yang dicentang yang akan lanjut ke flow checkout.</p>
          <p class="cart-summary-helper">Estimasi pajak dihitung otomatis dari subtotal item terpilih dengan tarif pajak keranjang saat ini sebesar {{ number_format((float) $object->persentasi_pajak * 100, 1, ',', '.') }}%.</p>
        </div>
      </aside>
    @endif
  </div>
@endsection

