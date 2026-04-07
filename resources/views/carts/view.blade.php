@extends('layouts.app')

@section('title', 'Shopping Cart | Spare Soko')

@section('content')
  <section class="page-hero page-hero-compact">
    <span class="eyebrow">Keranjang</span>
    <h1>Kelola item yang ingin dibeli sebelum lanjut checkout.</h1>
    <p>Pilih semua, pilih per produk, ubah jumlah, dan lanjutkan checkout hanya dengan item yang benar-benar Anda centang.</p>
  </section>

  <div class="cart-shell">
    @if ($object->cartItems->count() < 1)
      @include('carts.empty_cart')
    @else
      @php
        $groupedItems = $object->cartItems->groupBy(fn ($item) => $item->item->product->brand_name ?: 'Produk Lainnya');
      @endphp

      <div class="cart-main" id="cart-main-content">
        <div class="cart-toolbar-card">
          <label class="cart-selector-label">
            <input type="checkbox" id="cart-select-all" @checked($object->all_items_selected)>
            <span>Pilih Semua ({{ $object->cartItems->count() }})</span>
          </label>

          <form id="remove-selected-form" method="POST" action="{{ route('cart.remove_selected') }}">
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
                  <img src="{{ $item->product_image_url }}" alt="{{ $item->item->product->title }}">
                </div>

                <div class="cart-item-copy">
                  <h3>{{ $item->item->product->title }}</h3>
                  <p>{{ $item->item->title }}</p>
                  <div class="cart-item-meta">
                    <span class="account-badge">{{ $item->item->product->brand_name ?: 'Spare Part' }}</span>
                    <span class="cart-unit-price">{{ $item->item->formatted_effective_price }} / item</span>
                  </div>
                </div>

                <div class="cart-item-side">
                  <strong id="item-line-total-{{ $item->id }}">{{ rupiah_catalog($item->line_item_total) }}</strong>
                  <div class="cart-item-actions">
                    <a href="{{ $item->remove_url }}" class="cart-remove-link">Hapus</a>
                    <form action="." method="GET" class="cart-qty-form">
                      <input type="hidden" name="item" value="{{ $item->variation_id }}">
                      <input type="number" class="item-qty form-control" data-cart-item-id="{{ $item->id }}" name="qty" value="{{ $item->quantity }}" min="0">
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
            <strong id="cart-selected-tax">{{ rupiah_catalog($object->selected_tax_total) }}</strong>
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
        </div>
      </aside>
    @endif
  </div>
@endsection

@push('scripts')
  <script>
    (function ($) {
      function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
      }

      function selectedItemIdsFrom(selector) {
        return $(selector).map(function () {
          return $(this).data('cart-item-id');
        }).get();
      }

      function updateBrandCheckboxes() {
        $('[data-brand-card]').each(function () {
          var card = $(this);
          var itemCheckboxes = card.find('.cart-item-selector');
          var checkedCount = itemCheckboxes.filter(':checked').length;
          card.find('.cart-brand-selector').prop('checked', checkedCount > 0 && checkedCount === itemCheckboxes.length);
        });
      }

      function updateSummaryUi(response) {
        $('#cart-selected-count').text(response.selected_count || 0);
        $('#cart-selected-subtotal').text(formatCatalogRupiah(response.selected_subtotal));
        $('#cart-selected-tax').text(formatCatalogRupiah(response.selected_tax_total));
        $('#cart-selected-total').text(formatCatalogRupiah(response.selected_total));
        $('#cart-select-all').prop('checked', !!response.all_selected);
        $('#remove-selected-form button').prop('disabled', (response.selected_count || 0) < 1);
        $('#cart-checkout-button')
          .prop('disabled', (response.selected_count || 0) < 1)
          .text('Beli (' + (response.selected_count || 0) + ')');
        updateBrandCheckboxes();
      }

      function removeEmptyGroups() {
        $('[data-brand-card]').each(function () {
          if (!$(this).find('[data-cart-item-row]').length) {
            $(this).remove();
          }
        });
      }

      function showCartEmptyState() {
        $('#cart-main-content').html(@json(view('carts.empty_cart')->render()));
        $('.cart-summary-panel').remove();
      }

      function postSelection(itemIds, selected) {
        return $.ajax({
          type: 'POST',
          url: "{{ route('cart.selection') }}",
          data: {
            _token: csrfToken(),
            selected: selected ? 1 : 0,
            cart_item_ids: itemIds
          },
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
      }

      $(document).on('change', '.item-qty', function () {
        var input = $(this);
        var item = input.closest('form').find("input[name='item']").val();
        var qty = input.val();
        var cartItemId = input.data('cart-item-id');

        $.ajax({
          type: 'GET',
          url: "{{ route('cart') }}",
          data: { item: item, qty: qty },
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (data) {
            if (data.deleted && data.removed_item_id) {
              $('#cart-item-' + data.removed_item_id).remove();
              removeEmptyGroups();
            } else {
              $('#item-line-total-' + cartItemId).text(formatCatalogRupiah(data.line_total));
            }

            updateSummaryUi(data);

            if ((data.total_items || 0) === 0) {
              showCartEmptyState();
            }

            showFlashMessage(data.flash_message || 'Item updated');
            updateCartItemCount();
          },
          error: function () {
            window.location.href = "{{ route('cart') }}?item=" + item + "&qty=" + qty;
          }
        });
      });

      $(document).on('change', '.cart-item-selector', function () {
        var checkbox = $(this);

        postSelection([checkbox.data('cart-item-id')], checkbox.is(':checked'))
          .done(function (response) {
            updateSummaryUi(response);
          });
      });

      $(document).on('change', '.cart-brand-selector', function () {
        var checkbox = $(this);
        var ids = String(checkbox.data('cart-item-ids')).split(',').filter(Boolean);

        postSelection(ids, checkbox.is(':checked'))
          .done(function (response) {
            ids.forEach(function (id) {
              $('.cart-item-selector[data-cart-item-id="' + id + '"]').prop('checked', checkbox.is(':checked'));
            });
            updateSummaryUi(response);
          });
      });

      $(document).on('change', '#cart-select-all', function () {
        var checkbox = $(this);
        var ids = selectedItemIdsFrom('.cart-item-selector');

        postSelection(ids, checkbox.is(':checked'))
          .done(function (response) {
            $('.cart-item-selector, .cart-brand-selector').prop('checked', checkbox.is(':checked'));
            updateSummaryUi(response);
          });
      });

      $(document).on('submit', '#remove-selected-form', function (event) {
        event.preventDefault();

        $.ajax({
          type: 'POST',
          url: "{{ route('cart.remove_selected') }}",
          data: {
            _token: csrfToken()
          },
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          success: function (response) {
            $('.cart-item-selector:checked').each(function () {
              $('#cart-item-' + $(this).data('cart-item-id')).remove();
            });
            removeEmptyGroups();
            updateSummaryUi(response);

            if ((response.total_items || 0) === 0) {
              showCartEmptyState();
            }

            showFlashMessage(response.flash_message || 'Item terpilih berhasil dihapus.');
            updateCartItemCount();
          }
        });
      });
    })(jQuery);
  </script>
@endpush
