@php($cardUrl = route('products.show', $product))
<article class="related-product-card">
  <a href="{{ $cardUrl }}" class="related-product-media">
    <img class="img-responsive" src="{{ $product->image_url }}" alt="{{ $product->judul }}">
  </a>
  <div class="related-product-body">
    @if ($product->defaultCategory)
      <span class="product-category">{{ $product->defaultCategory->title }}</span>
    @endif

    <h4>{{ $product->judul }}</h4>

    <div class="product-price">
      {{ $product->formatted_price }}
    </div>
  </div>
</article>

