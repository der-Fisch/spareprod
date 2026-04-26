@php($cardUrl = route('products.show', $product))
<article class="related-product-card">
  <a href="{{ $cardUrl }}" class="related-product-media">
    <img class="img-responsive" src="{{ $product->image_url }}" alt="{{ $product->title }}">
  </a>
  <div class="related-product-body">
    @if ($product->defaultCategory)
      <span class="product-category">{{ $product->defaultCategory->title }}</span>
    @endif

    <h4>{{ $product->title }}</h4>

    <div class="product-price">
      {{ $product->formatted_price }}
    </div>
  </div>
</article>
