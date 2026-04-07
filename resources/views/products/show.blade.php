@extends('layouts.app')

@section('title', $product->title . ' | Spare Soko')

@section('content')
  <div class="row product-detail-layout">
    <div class="col-md-7">
      @php($galleryImages = $product->images->isNotEmpty() ? $product->images : collect([(object) ['image_url' => $product->image_url, 'alt_text' => $product->title]]))
      @php($firstVariation = $product->variations->first())
      @php($hasSkuData = filled($product->sku) || filled($product->oem_number))
      @php($hasBrandData = filled($product->brand_name) || filled($product->brand_type))
      @php($hasTechnicalSpecs = !empty($product->technical_specs))
      @php($hasWarranty = filled($product->warranty_label))
      @php($hasRating = filled($product->rating_value))
      <div class="product-detail-media">
        <div class="product-gallery" data-product-gallery>
          <div class="product-gallery-stage">
            @foreach ($galleryImages as $index => $image)
              <figure class="product-gallery-slide{{ $index === 0 ? ' is-active' : '' }}" data-gallery-slide>
                <img class="img-responsive" src="{{ $image->image_url }}" alt="{{ $image->alt_text ?: $product->title }}">
              </figure>
            @endforeach

            @if ($galleryImages->count() > 1)
              <button type="button" class="product-gallery-toggle product-gallery-toggle-prev" data-gallery-prev aria-label="Gambar sebelumnya">
                <i class="fa fa-angle-left"></i>
              </button>
              <button type="button" class="product-gallery-toggle product-gallery-toggle-next" data-gallery-next aria-label="Gambar berikutnya">
                <i class="fa fa-angle-right"></i>
              </button>
            @endif
          </div>
        </div>
      </div>

      <div class="info-card">
        <h3>Ringkasan Produk</h3>
        <p class="lead">{{ $product->description }}</p>
        <div class="product-overview-stack">
          @if ($hasSkuData || $hasBrandData)
            <div class="product-overview-grid">
              @if ($hasSkuData)
                <section class="product-overview-panel">
                  <span class="product-section-label">Nomor Part / SKU</span>
                  <div class="product-copy-list">
                    @if (filled($product->sku))
                      <div class="product-copy-item">
                        <div>
                          <span class="product-micro-label">SKU</span>
                          <strong>{{ $product->sku }}</strong>
                        </div>
                        <button type="button" class="product-copy-button" data-copy-text="{{ $product->sku }}">Salin</button>
                      </div>
                    @endif
                    @if (filled($product->oem_number))
                      <div class="product-copy-item">
                        <div>
                          <span class="product-micro-label">OEM</span>
                          <strong>{{ $product->oem_number }}</strong>
                        </div>
                        <button type="button" class="product-copy-button" data-copy-text="{{ $product->oem_number }}">Salin</button>
                      </div>
                    @endif
                  </div>
                </section>
              @endif

              @if ($hasBrandData)
                <section class="product-overview-panel">
                  <span class="product-section-label">Merek & Tipe</span>
                  <div class="product-micro-grid">
                    @if (filled($product->brand_name))
                      <div class="product-micro-item">
                        <span class="product-micro-label">Merek</span>
                        <strong>{{ $product->brand_name }}</strong>
                      </div>
                    @endif
                    @if (filled($product->brand_type))
                      <div class="product-micro-item">
                        <span class="product-micro-label">Tipe</span>
                        <strong><span class="product-chip product-chip-type">{{ $product->brand_type }}</span></strong>
                      </div>
                    @endif
                    <div class="product-micro-item">
                      <span class="product-micro-label">Kategori</span>
                      <strong>{{ $product->defaultCategory?->title ?? 'Umum' }}</strong>
                    </div>
                    <div class="product-micro-item">
                      <span class="product-micro-label">Varian</span>
                      <strong>{{ $product->variations->count() }} pilihan</strong>
                    </div>
                  </div>
                </section>
              @endif
            </div>
          @endif

          @if ($hasTechnicalSpecs)
            <section class="product-overview-panel">
              <span class="product-section-label">Spesifikasi Teknis</span>
              <div class="product-spec-grid product-spec-grid-detail">
                @foreach ($product->technical_specs as $label => $value)
                  <div class="product-spec-item">
                    <span>{{ $label }}</span>
                    <strong>{{ $value }}</strong>
                  </div>
                @endforeach
              </div>
            </section>
          @endif

          <div class="product-overview-grid">
            @if ($hasWarranty)
              <section class="product-overview-panel">
                <span class="product-section-label">Garansi</span>
                <div class="product-summary-item">
                  <strong>{{ $product->warranty_label }}</strong>
                </div>
              </section>
            @endif

            <section class="product-overview-panel">
              <span class="product-section-label">Ketersediaan Stok</span>
              <div class="product-summary-item">
                <strong id="detail-stock-display">{{ $firstVariation?->stock_display_label ?? $product->stock_display_label }}</strong>
                <span id="detail-stock-badge" class="product-chip {{ $firstVariation?->stock_badge_class ?? $product->stock_badge_class }}">{{ $firstVariation?->stock_badge_label ?? $product->stock_badge_label }}</span>
              </div>
            </section>

            @if ($hasRating)
              <section class="product-overview-panel">
                <span class="product-section-label">Rating</span>
                <div class="product-summary-item product-rating-inline">
                  <strong><i class="fa fa-star"></i> {{ $product->rating_value }}</strong>
                </div>
              </section>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="purchase-card">
        <div class="purchase-card-header">
          <span class="eyebrow">Purchase Panel</span>
          <h3>Ready to add this part?</h3>
        </div>

        <form id="add-form" method="GET" action="{{ route('cart') }}">
          <p id="jquery-message" class="lead"></p>
          <div class="purchase-price" id="price">
            @if ($firstVariation?->sale_price)
              <span class="sale-price">{{ $firstVariation->formatted_sale_price }}</span>
              <span class="og-price">{{ $firstVariation->formatted_price }}</span>
            @elseif ($firstVariation)
              {{ $firstVariation->formatted_price }}
            @else
              {{ $product->formatted_price }}
            @endif
          </div>

          @if ($product->variations->count() > 1)
            <label class="field-label" for="variation-select">Variation</label>
            <select id="variation-select" name="item" class="form-control variation_select">
              @foreach ($product->variations as $variation)
                <option
                  value="{{ $variation->id }}"
                  data-sale-price-formatted="{{ $variation->formatted_sale_price }}"
                  data-price-formatted="{{ $variation->formatted_price }}"
                  data-stock-display-label="{{ $variation->stock_display_label }}"
                  data-stock-badge-label="{{ $variation->stock_badge_label }}"
                  data-stock-badge-class="{{ $variation->stock_badge_class }}"
                  data-inventory="{{ $variation->inventory }}"
                >
                  {{ $variation->title }}
                </option>
              @endforeach
            </select>
          @elseif ($firstVariation)
            <input type="hidden" name="item" value="{{ $firstVariation->id }}">
            <div class="variant-badge">{{ $firstVariation->title }}</div>
          @endif

          <div class="variant-stock-note">
            <span class="product-micro-label">Stok Varian</span>
            <div class="variant-stock-copy">
              <strong id="variant-stock-display">{{ $firstVariation?->stock_display_label ?? $product->stock_display_label }}</strong>
              <span id="variant-stock-badge" class="product-chip {{ $firstVariation?->stock_badge_class ?? $product->stock_badge_class }}">{{ $firstVariation?->stock_badge_label ?? $product->stock_badge_label }}</span>
            </div>
          </div>

          <label class="field-label" for="qty-input">Quantity</label>
          <input id="qty-input" class="form-control" type="number" name="qty" value="1" min="1">

          <div class="purchase-actions">
            <button id="submit-btn" type="submit" class="btn btn-primary btn-block">Add to Cart</button>
            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-block">Back to Catalog</a>
          </div>
        </form>

        <div class="purchase-note">
          <i class="fa fa-shield"></i>
          Pastikan SKU, OEM, dan varian yang dipilih sudah sesuai sebelum checkout.
        </div>
      </div>

      <div class="info-card">
        <h4>Bagikan Produk</h4>
        <div class="share-links">
          <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}"><i class="fa fa-facebook-square"></i> Facebook</a>
          <a href="javascript:void(0)"><i class="fa fa-twitter-square"></i> Twitter</a>
        </div>
      </div>

      <div class="info-card">
        <div class="section-heading section-heading-tight">
          <div>
            <span class="eyebrow">Produk Terkait</span>
            <h3>Produk lain yang mungkin Anda butuhkan</h3>
          </div>
        </div>

        <div class="row related-product-list">
          @forelse ($relatedProducts as $relatedProduct)
            <div class="col-xs-6">
              @include('products._related_card', ['product' => $relatedProduct])
            </div>
          @empty
            <div class="col-xs-12">
              <p class="text-muted">Belum ada produk terkait yang tersedia.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    (function ($) {
      function setStock() {
        var selectedOption = $(".variation_select option:selected");
        if (!selectedOption.length) {
          return;
        }

        var stockDisplay = selectedOption.attr("data-stock-display-label");
        var stockBadgeLabel = selectedOption.attr("data-stock-badge-label");
        var stockBadgeClass = selectedOption.attr("data-stock-badge-class");

        $("#detail-stock-display, #variant-stock-display").text(stockDisplay);
        $("#detail-stock-badge, #variant-stock-badge")
          .text(stockBadgeLabel)
          .removeClass("product-chip-stock-ok product-chip-stock-low product-chip-stock-out")
          .addClass(stockBadgeClass);
      }

      function setPrice() {
        var selectedOption = $(".variation_select option:selected");
        var price = selectedOption.attr("data-price-formatted");
        var salePrice = selectedOption.attr("data-sale-price-formatted");

        if (salePrice && salePrice !== "null") {
          $("#price").html("<span class='sale-price'>" + salePrice + "</span> <span class='og-price'>" + price + "</span>");
        } else {
          $("#price").html(price);
        }
      }

      function setupGallery() {
        var gallery = $("[data-product-gallery]");
        if (!gallery.length) {
          return;
        }

        var slides = gallery.find("[data-gallery-slide]");
        if (slides.length <= 1) {
          return;
        }

        var activeIndex = 0;

        function renderGallery(index) {
          activeIndex = (index + slides.length) % slides.length;
          slides.removeClass("is-active").eq(activeIndex).addClass("is-active");
        }

        gallery.on("click", "[data-gallery-prev]", function () {
          renderGallery(activeIndex - 1);
        });

        gallery.on("click", "[data-gallery-next]", function () {
          renderGallery(activeIndex + 1);
        });
      }

      $(document).ready(function () {
        setupGallery();
        setPrice();
        setStock();

        $(".variation_select").on("change", function () {
          setPrice();
          setStock();
        });

        $("#submit-btn").on("click", function (event) {
          event.preventDefault();
          var formData = $("#add-form").serialize();

          $.ajax({
            type: "GET",
            url: "{{ route('cart') }}",
            data: formData,
            headers: {
              "X-Requested-With": "XMLHttpRequest"
            },
            success: function (data) {
              updateCartItemCount();
              showCartSuccessAlert(data.flash_message || "Produk berhasil ditambahkan ke cart.");
            },
            error: function () {
              $("#add-form").trigger("submit");
            }
          });
        });
      });
    })(jQuery);
  </script>
@endpush
