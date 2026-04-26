<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\UserCheckout;
use App\Models\Category;
use App\Models\Product;
use App\Models\Variation;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedStore(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(StoreSeeder::class);
    }

    public function test_add_to_cart_returns_json_for_ajax_requests(): void
    {
        $this->seed(CatalogSeeder::class);
        $variation = Variation::query()->firstOrFail();

        $response = $this->post('/cart/items', [
            'variation_id' => $variation->id,
            'quantity' => 1,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJson([
            'item_added' => true,
            'deleted' => false,
        ]);
    }

    public function test_authenticated_user_can_view_account_settings(): void
    {
        $this->seedStore();
        $user = User::query()->where('username', 'raka.saputra')->firstOrFail();

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
        $response->assertSee('Account Settings', false);
    }

    public function test_staff_user_can_still_view_account_settings(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/settings');

        $response->assertOk();
        $response->assertSee('Admin Settings', false);
        $response->assertSee('Profil Admin', false);
        $response->assertSee('Ganti password admin', false);
        $response->assertDontSee('Daftar Alamat', false);
        $response->assertDontSee('Pembayaran', false);
    }

    public function test_staff_user_can_update_admin_specific_settings(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->post('/settings', [
            'action' => 'profile',
            'username' => 'admin_ops',
            'email' => 'admin.ops@sparesoko.test',
            'whatsapp_number' => '081200001111',
        ]);

        $response->assertRedirect('/settings');

        $admin->refresh();

        $this->assertSame('admin_ops', $admin->username);
        $this->assertSame('admin.ops@sparesoko.test', $admin->email);
        $this->assertSame('081200001111', $admin->accountProfile->fresh()->whatsapp_number);
    }

    public function test_guest_checkout_session_can_view_order_detail(): void
    {
        $this->seedStore();
        $checkout = UserCheckout::query()->where('email', 'raka@sparesoko.test')->firstOrFail();
        $order = Order::query()->where('user_checkout_id', $checkout->id)->firstOrFail();

        $response = $this->withSession(['user_checkout_id' => $checkout->id])->get('/orders/' . $order->id);

        $response->assertOk();
        $response->assertSee('Order Detail', false);
    }

    public function test_authenticated_user_can_see_order_id_and_product_names_in_order_history(): void
    {
        $this->seedStore();
        $user = User::query()->where('username', 'raka.saputra')->firstOrFail();
        $order = Order::query()->where('user_checkout_id', UserCheckout::query()->where('user_id', $user->id)->value('id'))->firstOrFail();
        $firstProductName = $order->cart->cartItems()->with('item.product')->firstOrFail()?->item?->product?->title;

        $response = $this->actingAs($user)->get('/orders');

        $response->assertOk();
        $response->assertSee('ID: ' . $order->id, false);
        $response->assertSee($firstProductName, false);
    }

    public function test_staff_user_can_open_admin_dashboard(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Dashboard', false);
    }

    public function test_staff_user_is_redirected_away_from_storefront_routes(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/products');

        $response->assertRedirect('/admin');
    }

    public function test_staff_login_redirects_to_admin_dashboard(): void
    {
        $this->seedStore();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password123',
            'next' => '/products',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_admin_product_update_changes_user_facing_product_data(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $product = Product::query()->where('title', 'Battery Terminal Clamp')->firstOrFail();

        $response = $this->actingAs($admin)->post(
            '/admin/products/' . $product->id . '/edit',
            [
                'title' => 'Battery Terminal Clamp',
                'description' => 'Clamp baterai revisi admin.',
                'sku' => 'BTC-NEW-777',
                'brand_name' => 'Bosch Update',
                'warranty_label' => 'Garansi Admin 14 Hari',
                'price' => '14.50',
                'stok' => '9',
                'category_id' => (string) $product->default_category_id,
                'compatibility_entries' => [
                    ['vehicle_name' => 'Suzuki Ertiga', 'year_start' => '2018', 'year_end' => '2022'],
                    ['vehicle_name' => 'Toyota Avanza', 'year_start' => '2019', 'year_end' => '2023'],
                ],
                'specification_entries' => [
                    ['label' => 'Bahan', 'value' => 'Aluminium'],
                    ['label' => 'Tegangan', 'value' => '12V'],
                ],
                'image_entries' => [
                    ['image_path' => 'theme/img/produk/battery-terminal-clamp.jpg', 'alt_text' => 'Clamp utama'],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Clamp kedua'],
                ],
                'active' => '1',
            ],
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $product->refresh();

        $this->assertSame('BTC-NEW-777', $product->sku);
        $this->assertSame('Bosch Update', $product->brand_name);
        $this->assertSame('Garansi Admin 14 Hari', $product->warranty_label);
        $this->assertSame(9, $product->stok);
        $this->assertDatabaseHas('product_compatibilities', [
            'product_id' => $product->id,
            'vehicle_name' => 'Suzuki Ertiga',
            'year_start' => 2018,
            'year_end' => 2022,
        ]);
        $this->assertDatabaseHas('product_specifications', [
            'product_id' => $product->id,
            'label' => 'Bahan',
            'value' => 'Aluminium',
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'image_path' => 'theme/img/marketing1.jpg',
        ]);

        $this->post('/logout');

        $storefrontResponse = $this->get('/products/' . $product->id);

        $storefrontResponse->assertOk();
        $storefrontResponse->assertSee('BTC-NEW-777', false);
        $storefrontResponse->assertSee('Bosch Update', false);
        $storefrontResponse->assertSee('Garansi Admin 14 Hari', false);
        $storefrontResponse->assertSee('Clamp baterai revisi admin.', false);
        $storefrontResponse->assertSee('9 unit', false);
    }

    public function test_admin_can_create_product_with_minimal_required_fields(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $category = Category::query()->where('slug', 'brakes')->firstOrFail();

        $response = $this->actingAs($admin)->post('/admin/products/create', [
            'title' => 'Minimal Produk',
            'price' => '12.50',
            'stok' => '30',
            'category_id' => (string) $category->id,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', [
            'title' => 'Minimal Produk',
            'default_category_id' => $category->id,
            'stok' => 30,
        ]);
    }

    public function test_admin_can_create_edit_and_delete_category_via_modal_endpoints(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $category = Category::query()->where('slug', 'brakes')->firstOrFail();

        $createModal = $this->actingAs($admin)->get('/admin/categories/create', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $createModal->assertOk();
        $createModal->assertJsonStructure(['html']);

        $createResponse = $this->actingAs($admin)->post('/admin/categories/create', [
            'title' => 'Cooling',
            'description' => 'Cooling parts',
            'active' => '1',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $createResponse->assertOk();
        $createResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('categories', ['slug' => 'cooling', 'title' => 'Cooling']);

        $editModal = $this->actingAs($admin)->get('/admin/categories/' . $category->id . '/edit', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $editModal->assertOk();
        $editModal->assertJsonStructure(['html']);

        $editResponse = $this->actingAs($admin)->post('/admin/categories/' . $category->id . '/edit', [
            'title' => 'Brake Systems',
            'description' => 'Updated description',
            'active' => '1',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $editResponse->assertOk();
        $editResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'title' => 'Brake Systems', 'slug' => 'brake-systems']);

        $deleteModal = $this->actingAs($admin)->get('/admin/categories/' . $category->id . '/delete', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $deleteModal->assertOk();
        $deleteModal->assertJsonStructure(['html']);

        $deleteResponse = $this->actingAs($admin)->post('/admin/categories/' . $category->id . '/delete', [], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $deleteResponse->assertOk();
        $deleteResponse->assertJson(['success' => true]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_checkout_reduces_inventory_for_purchased_variations_only(): void
    {
        $this->seedStore();
        $customer = User::query()->where('username', 'raka.saputra')->firstOrFail();
        $order = Order::query()->with('cart.cartItems.item')->firstOrFail();
        $cartItems = $order->cart->cartItems->values();

        $this->assertGreaterThanOrEqual(2, $cartItems->count());

        $expectedReductions = $cartItems
            ->groupBy(fn ($item) => $item->item->product_id)
            ->map(fn ($items) => $items->sum('quantity'));

        $beforeStocks = Product::query()
            ->whereIn('id', $expectedReductions->keys())
            ->pluck('stok', 'id');

        $untouchedProduct = Product::query()
            ->whereNotIn('id', $expectedReductions->keys())
            ->firstOrFail();
        $untouchedBefore = $untouchedProduct->stok;

        $response = $this->actingAs($customer)
            ->withSession([
                'cart_id' => $order->cart_id,
                'order_id' => $order->id,
                'user_checkout_id' => $order->user_checkout_id,
            ])
            ->post('/checkout/final');

        $response->assertRedirect('/orders/' . $order->id);

        $this->assertSame('created', $order->fresh()->status);
        foreach ($expectedReductions as $productId => $reduction) {
            $this->assertSame(
                (int) $beforeStocks[$productId] - (int) $reduction,
                Product::query()->findOrFail($productId)->stok
            );
        }
        $this->assertSame($untouchedBefore, $untouchedProduct->fresh()->stok);
    }

    public function test_cod_checkout_creates_order_without_marking_it_paid(): void
    {
        $this->seedStore();
        $customer = User::query()->where('username', 'raka.saputra')->firstOrFail();
        $order = Order::query()->with('cart.cartItems.item')->firstOrFail();

        $cartItems = $order->cart->cartItems->values();
        $productId = $cartItems[0]->item->product_id;
        $expectedReduction = (int) $cartItems
            ->filter(fn ($item) => $item->item->product_id === $productId)
            ->sum('quantity');
        $product = Product::query()->findOrFail($productId);
        $before = $product->stok;

        $response = $this->actingAs($customer)
            ->withSession([
                'cart_id' => $order->cart_id,
                'order_id' => $order->id,
                'user_checkout_id' => $order->user_checkout_id,
            ])
            ->post('/checkout/final');

        $response->assertRedirect('/orders/' . $order->id);

        $this->assertSame('cod', $order->fresh()->payment_method);
        $this->assertSame('created', $order->fresh()->status);
        $this->assertSame($before - $expectedReduction, $product->fresh()->stok);
    }

    public function test_checkout_redirects_back_to_cart_when_selected_item_is_out_of_stock(): void
    {
        $this->seedStore();
        $customer = User::query()->where('username', 'raka.saputra')->firstOrFail();
        $order = Order::query()->with('cart.cartItems.item.product')->firstOrFail();

        $order->cart->cartItems()->update(['is_selected' => true]);

        $outOfStockProduct = $order->cart->cartItems->firstOrFail()->item->product;
        $outOfStockProduct->stok = 0;
        $outOfStockProduct->save();

        $response = $this->actingAs($customer)
            ->withSession([
                'cart_id' => $order->cart_id,
            ])
            ->get('/checkout');

        $response->assertRedirect('/cart');
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'Silakan tunggu admin/staff melakukan restock.',
            session('error')
        );
    }
}

