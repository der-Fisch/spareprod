@php($cardUrl = $productUrl ?? route('products.show', $product))
<article class="product-card">
  <a href="{{ $cardUrl }}" class="product-card-media">
    <img class="img-responsive" src="{{ $product->image_url }}" alt="{{ $product->title }}">
  </a>
  <div class="product-card-body">
    <div class="product-card-topline">
      @if ($product->defaultCategory)
        <span class="product-category">{{ $product->defaultCategory->title }}</span>
      @endif
      <span class="product-chip {{ $product->stock_badge_class }}">{{ $product->stock_badge_label }}</span>
    </div>

    <h3>
      <a href="{{ $cardUrl }}">{{ $product->title }}</a>
    </h3>

    <p class="product-description">{{ $product->description }}</p>

    <div class="product-card-footer">
      <div class="product-price">
        {{ $product->formatted_price }}
      </div>
      <a href="{{ $cardUrl }}" class="btn btn-outline">Lihat Detail</a>
    </div>
  </div>
</article>
