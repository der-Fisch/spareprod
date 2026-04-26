@extends('layouts.app')

@section('title', $product->judul . ' | Spare Soko')

@section('content')
  <div class="row product-detail-layout">
    <div class="col-md-7">
      @php($galleryImages = $product->images->isNotEmpty() ? $product->images : collect([(object) ['image_url' => $product->image_url, 'alt_text' => $product->judul]]))
      @php($hasSkuData = filled($product->sku))
      @php($hasBrandData = filled($product->brand_name))
      @php($hasTechnicalSpecs = !empty($product->technical_specs))
      @php($hasWarranty = filled($product->warranty_label))
      <div class="product-detail-media">
        <div class="product-gallery" data-product-gallery>
          <div class="product-gallery-stage">
            @foreach ($galleryImages as $index => $image)
              <figure class="product-gallery-slide{{ $index === 0 ? ' is-active' : '' }}" data-gallery-slide>
                <img class="img-responsive" src="{{ $image->image_url }}" alt="{{ $image->alt_text ?: $product->judul }}">
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
        <h3>Product Summary</h3>
        <p class="lead">{{ $product->deskripsi }}</p>
        <div class="product-overview-stack">
          @if ($hasSkuData || $hasBrandData)
            <div class="product-overview-grid">
              @if ($hasSkuData)
                <section class="product-overview-panel">
                  <span class="product-section-label">Product SKU</span>
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
                  </div>
                </section>
              @endif

              @if ($hasBrandData)
                <section class="product-overview-panel">
                  <span class="product-section-label">Merek & Kategori</span>
                  <div class="product-micro-grid">
                    @if (filled($product->brand_name))
                      <div class="product-micro-item">
                        <span class="product-micro-label">Merek</span>
                        <strong>{{ $product->brand_name }}</strong>
                      </div>
                    @endif
                    <div class="product-micro-item">
                      <span class="product-micro-label">Kategori</span>
                      <strong>{{ $product->defaultCategory?->title ?? 'Umum' }}</strong>
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
                <strong>{{ $product->stock_display_label }}</strong>
                <span class="product-chip {{ $product->stock_badge_class }}">{{ $product->stock_badge_label }}</span>
              </div>
            </section>

          </div>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="purchase-card">
        <div class="purchase-card-header">
          <span class="eyebrow">Panel Pembelian</span>
          <h3>Siap menambahkan produk ini?</h3>
        </div>

        <form
          id="add-form"
          method="POST"
          action="{{ route('cart.items.store') }}"
          data-cart-add-form
        >
          @csrf
          <div class="purchase-price" id="price">
            {{ $product->formatted_price }}
          </div>

          @if ($cartItemId)
            <input type="hidden" name="variation_id" value="{{ $cartItemId }}">
          @endif

          <div class="variant-stock-note">
            <span class="product-micro-label">Product Stock</span>
            <div class="variant-stock-copy">
              <strong>{{ $product->stock_display_label }}</strong>
              <span class="product-chip {{ $product->stock_badge_class }}">{{ $product->stock_badge_label }}</span>
            </div>
          </div>

          <label class="field-label" for="qty-input">Jumlah</label>
          <input id="qty-input" class="form-control" type="number" name="quantity" value="1" min="1">

          <div class="purchase-actions">
            <button id="submit-btn" type="submit" class="btn btn-primary btn-block" @disabled(! $cartItemId)>Add to Cart</button>
            <a href="{{ route('products.index') }}" class="btn btn-secondary btn-block">Kembali ke Katalog</a>
          </div>
        </form>

        <div class="purchase-note">
          <i class="fa fa-shield"></i>
          Pastikan SKU dan jumlah produk sudah sesuai sebelum checkout.
        </div>
      </div>

      <div class="info-card">
        <h4>Share Product</h4>
        <div class="share-links">
          <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}"><i class="fa fa-facebook-square"></i> Facebook</a>
          <a href="javascript:void(0)"><i class="fa fa-twitter-square"></i> Twitter</a>
        </div>
      </div>

      <div class="info-card">
        <div class="section-heading section-heading-tight">
          <div>
            <span class="eyebrow">Related Products</span>
            <h3>Products lain yang mungkin Anda butuhkan</h3>
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

