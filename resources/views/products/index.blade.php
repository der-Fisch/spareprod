@extends('layouts.app')

@section('title', 'Product Catalog | Spare Soko')

@section('content')
  @php($hasActiveFilters = filled($filters['q'] ?? null) || filled($filters['category_id'] ?? null) || filled($filters['min_price'] ?? null) || filled($filters['max_price'] ?? null))
  <section class="page-hero">
    <span class="eyebrow">Product Catalog</span>
    <h1>Semua produk spare part dalam satu tampilan yang lebih tertata.</h1>
    <p>Cari komponen berdasarkan kategori, kata kunci, atau rentang harga. Ini adalah titik awal migrasi katalog Django ke Laravel dengan visual yang tetap konsisten.</p>
  </section>

  <section class="catalog-shell">
    <aside class="filter-card catalog-filter-card">
      <div class="filter-card-header">
        <span class="eyebrow">Product Filters</span>
        <a href="{{ route('products.index') }}" class="section-link">Reset</a>
      </div>

      <form method="GET" action="{{ route('products.index') }}" class="catalog-filter-form">
        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
        <div class="catalog-filter-grid">
          <div class="filter-field">
            <label class="field-label" for="category_id">Categories</label>
            <select id="category_id" name="category_id" class="form-control">
              <option value="">Semua kategori</option>
              @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->title }}</option>
              @endforeach
            </select>
          </div>

          <div class="filter-field">
            <label class="field-label" for="min_price">Min Price</label>
            <div class="price-input-shell">
              <span>Rp</span>
              <input id="min_price" type="text" name="min_price" class="form-control" data-price-format value="{{ $filters['min_price'] ?? '' }}" placeholder="0.000">
            </div>
          </div>

          <div class="filter-field">
            <label class="field-label" for="max_price">Max Price</label>
            <div class="price-input-shell">
              <span>Rp</span>
              <input id="max_price" type="text" name="max_price" class="form-control" data-price-format value="{{ $filters['max_price'] ?? '' }}" placeholder="500.000">
            </div>
          </div>

          <div class="filter-field filter-field-action">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
          </div>
        </div>
      </form>
    </aside>

    <div id="catalog-results">
      <div class="catalog-results-meta">
        <span class="eyebrow">Inventory Results</span>
        <h2>{{ $resultsCount }} produk {{ $hasActiveFilters ? 'ditemukan' : 'tersedia' }}</h2>
        <p>Katalog sparepart ditampilkan dalam layout yang lebih rapi, dengan tombol aksi dan harga yang lebih mudah dipindai.</p>
      </div>

      <div class="row catalog-product-grid">
        @forelse ($products as $product)
          <div class="col-xs-12 col-sm-6 col-md-4 catalog-product-col">
            @include('products._card', ['product' => $product])
          </div>
        @empty
          <div class="col-sm-12">
            <div class="empty-state-card">
              <h3>No products available.</h3>
              <p>Tambahkan produk baru atau cek kembali filter yang sedang dipakai.</p>
            </div>
          </div>
        @endforelse
      </div>

      @if ($products->hasPages())
        <nav class="pagination-shell">
          @if ($products->onFirstPage())
            <span></span>
          @else
            <a href="{{ $products->previousPageUrl() }}" class="pagination-link" data-catalog-page>Sebelumnya</a>
          @endif

          <span class="pagination-status">Halaman {{ $products->currentPage() }} dari {{ $products->lastPage() }}</span>

          @if ($products->hasMorePages())
            <a href="{{ $products->nextPageUrl() }}" class="pagination-link" data-catalog-page>Berikutnya</a>
          @else
            <span></span>
          @endif
        </nav>
      @endif
    </div>
  </section>
@endsection

