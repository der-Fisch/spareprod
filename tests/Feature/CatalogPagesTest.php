<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_successfully(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Spare part kendaraan', false);
        $response->assertSee(route('login', ['next' => route('products.index')]));
        $response->assertSee('login?next=http%3A%2F%2Flocalhost%2Fproducts%2F', false);
    }

    public function test_products_index_renders_successfully(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('Product Catalog', false);
        $response->assertSee('produk tersedia', false);
        $response->assertSee('View Details', false);
    }

    public function test_product_detail_renders_successfully(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        $product = Product::query()->firstOrFail();

        $response = $this->get('/products/' . $product->id);

        $response->assertOk();
        $response->assertSee($product->title, false);
        $response->assertSee('Nomor Part / SKU', false);
        $response->assertSee('Spesifikasi Teknis', false);
        $response->assertSee('Rating', false);
        $response->assertDontSee('ulasan', false);
        $response->assertSee('Stok Varian', false);
        $response->assertSee('Gambar berikutnya', false);
        $response->assertDontSee('Kompatibilitas', false);
        $response->assertSee('data-stock-display-label', false);
    }

    public function test_auth_pages_render_the_static_forms(): void
    {
        $loginResponse = $this->get('/login?next=' . urlencode('/products'));

        $loginResponse->assertOk();
        $loginResponse->assertSee('name="next" value="/products"', false);
        $loginResponse->assertDontSee('Create Account', false);
        $loginResponse->assertDontSee('Account Access', false);

        $registerResponse = $this->get('/register?next=' . urlencode('/products'));

        $registerResponse->assertOk();
        $registerResponse->assertSee('name="next" value="/products"', false);
    }

    public function test_products_index_uses_found_label_when_filters_are_active(): void
    {
        $this->seed(DemoCatalogSeeder::class);

        $response = $this->get('/products?min_price=500000');

        $response->assertOk();
        $response->assertSee('produk ditemukan', false);
    }
}
