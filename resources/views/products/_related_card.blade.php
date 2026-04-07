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
      @php($firstVariation = $product->variations->first())
      @if ($firstVariation?->sale_price)
        <span class="sale-price">{{ $firstVariation->formatted_sale_price }}</span>
        <span class="og-price">{{ $firstVariation->formatted_price }}</span>
      @elseif ($firstVariation)
        {{ $firstVariation->formatted_price }}
      @else
        {{ $product->formatted_price }}
      @endif
    </div>
  </div>
</article>
