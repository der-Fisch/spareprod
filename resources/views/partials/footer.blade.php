<footer class="site-footer">
  <div class="landing-shell site-footer-grid">
    <div class="site-footer-brand">
      <a class="brand-lockup" href="{{ route('home') }}">
        <span class="brand-icon">
          <span>SS</span>
        </span>
        <span class="brand-copy">
          <strong>Spare Soko</strong>
          <small>Industrial Auto Parts</small>
        </span>
      </a>
      <p>Platform katalog spare part yang dirancang agar lebih rapi, mudah dipahami, dan siap dipresentasikan sebagai aplikasi yang matang.</p>
    </div>
    <div>
      <span class="eyebrow">Navigation</span>
      <ul class="footer-links list-unstyled">
        <li><a href="#hero" data-scroll-target="hero">Home</a></li>
        <li><a href="#why-choose-us" data-scroll-target="why-choose-us">Why Choose Us</a></li>
        <li><a href="#catalog-preview" data-scroll-target="catalog-preview">Preview</a></li>
        <li><a href="#vision-mission" data-scroll-target="vision-mission">Vision & Mission</a></li>
        <li><a href="#contact-cta" data-scroll-target="contact-cta">Contact</a></li>
      </ul>
    </div>
    <div>
      <span class="eyebrow">Storefront</span>
      <ul class="footer-links list-unstyled">
        <li><a href="{{ route('products.index') }}">Products</a></li>
        @auth
          <li><a href="{{ route('orders.index') }}">Orders</a></li>
          <li><a href="{{ route('account.settings') }}">Settings</a></li>
        @else
          <li><a href="{{ route('login') }}">Login</a></li>
          <li><a href="{{ route('register') }}">Register</a></li>
        @endauth
        <li><a href="#contact-cta" data-scroll-target="contact-cta">Hubungi Kami</a></li>
      </ul>
    </div>
  </div>
</footer>
