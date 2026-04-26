@extends('layouts.app')

@section('title', 'Spare Soko')

@section('content')
  @php($browseUrl = auth()->check() ? route('products.index') : route('login', ['next' => route('products.index')]))
  @php($productDetailLoginUrl = fn ($product) => auth()->check() ? route('products.show', $product) : route('login', ['next' => route('products.show', $product)]))
  <section class="landing-section landing-hero" id="hero">
    <div class="landing-shell landing-grid">
      <div class="landing-copy">
        <span class="landing-kicker">Trusted Spare Parts Platform</span>
        <h1>Spare part kendaraan yang tampil profesional, cepat dicari, dan siap dibeli.</h1>
        <p>Spare Soko menggabungkan katalog spare part, kategori yang rapi, dan storefront yang profesional untuk pengalaman belanja yang lebih meyakinkan.</p>
        <div class="landing-actions">
          <a href="{{ $browseUrl }}" class="btn btn-primary btn-lg">Jelajahi Produk</a>
          <a href="#catalog-preview" class="btn btn-ghost btn-lg" data-scroll-target="catalog-preview">Explore Preview</a>
        </div>
      </div>
      <div class="landing-visual-card">
        <img src="{{ $featuredProduct?->image_url ?? asset('theme/img/products/ceramic-brake-pad-set.jpg') }}" alt="{{ $featuredProduct?->title ?? 'Brake pad set' }}" class="landing-hero-image">
        <div class="landing-visual-caption">
          <span class="landing-kicker">Featured Pick</span>
          <h3>{{ $featuredProduct?->title ?? 'Ceramic Brake Pad Set' }}</h3>
          <p>Katalog dibuat untuk membantu pelanggan menemukan komponen yang tepat tanpa tampilan yang berantakan.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="landing-section landing-section-muted" id="catalog-preview">
    <div class="landing-shell">
      <div class="section-heading landing-heading">
        <div>
          <span class="eyebrow">Product Preview</span>
          <h2>Lihat pilihan produk unggulan dari katalog utama</h2>
        </div>
        <div class="landing-heading-actions">
          <a href="{{ $browseUrl }}" class="section-link">All Products</a>
        </div>
      </div>

      <div class="row landing-product-grid">
        @foreach ($products as $product)
          <div class="col-xs-12 col-sm-6 col-md-4">
            @include('products._card', ['product' => $product, 'productUrl' => $productDetailLoginUrl($product)])
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <section class="landing-section" id="why-choose-us">
    <div class="landing-shell">
      <div class="section-heading landing-heading">
        <div>
          <span class="eyebrow">Why Choose Us</span>
          <h2>Alasan kenapa pengalaman belanjanya terasa lebih meyakinkan</h2>
        </div>
      </div>
      <div class="value-grid">
        <article class="value-card">
          <i class="fa fa-filter"></i>
          <h3>Navigasi Lebih Jelas</h3>
          <p>Pelanggan bisa masuk dari landing page, lalu bergerak ke produk atau kategori dengan struktur yang lebih mudah dipahami.</p>
        </article>
        <article class="value-card">
          <i class="fa fa-bolt"></i>
          <h3>Pencarian Lebih Cepat</h3>
          <p>Produk dan kategori disusun agar user lebih cepat sampai ke barang yang dicari tanpa harus membuka banyak halaman.</p>
        </article>
        <article class="value-card">
          <i class="fa fa-shield"></i>
          <h3>Flow Belanja Rapi</h3>
          <p>Dari detail produk, cart, sampai order history, alurnya dirancang supaya terlihat seperti aplikasi toko yang sudah matang.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="landing-section" id="vision-mission">
    <div class="landing-shell">
      <div class="vision-layout">
        <article class="vision-card">
          <span class="eyebrow">Visi Kami</span>
          <h2>Menjadi storefront spare part yang modern, jelas, dan dipercaya pengguna.</h2>
          <p>Kami ingin setiap pengunjung langsung memahami produk, kategori, dan alur pembelian tanpa harus menebak-nebak.</p>
        </article>
        <article class="vision-card vision-card-accent">
          <span class="eyebrow">Misi Kami</span>
          <ul class="vision-list">
            <li>Menyajikan katalog spare part yang lebih rapi dan mudah ditelusuri.</li>
            <li>Membuat pengalaman belanja terasa siap dipakai untuk kebutuhan bisnis nyata.</li>
            <li>Menyediakan alur account, cart, dan order yang konsisten dari awal sampai akhir.</li>
          </ul>
        </article>
      </div>
    </div>
  </section>

  <section class="landing-section landing-cta" id="contact-cta">
    <div class="landing-shell landing-cta-layout">
      <div class="landing-cta-shell">
        <div>
          <span class="eyebrow">Get In Touch</span>
          <h2>Butuh bantuan mencari spare part yang tepat?</h2>
          <p>Hubungi kami untuk diskusi kebutuhan produk, kategori, atau alur pembelian yang paling sesuai.</p>
        </div>
        <div class="landing-actions landing-actions-stack">
          <a href="https://wa.me/628112456778?text=Halo%20Spare%20Soko%2C%20saya%20ingin%20bertanya%20tentang%20spare%20part." target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg">Hubungi Kami</a>
        </div>
      </div>
      <div class="contact-info-grid">
        <article class="contact-info-card">
          <span class="eyebrow">WhatsApp</span>
          <h3>+62 811 2456 778</h3>
          <p>Customer support area Bandung, Jawa Barat.</p>
        </article>
        <article class="contact-info-card">
          <span class="eyebrow">Email</span>
          <h3>support@sparesoko.co.id</h3>
          <p>Email operasional untuk area Semarang, Jawa Tengah.</p>
        </article>
        <article class="contact-info-card">
          <span class="eyebrow">Lokasi</span>
          <h3>Jl. Ahmad Yani No. 88, Surabaya</h3>
          <p>Warehouse dan distribusi area Jawa Timur.</p>
        </article>
      </div>
    </div>
  </section>
@endsection
