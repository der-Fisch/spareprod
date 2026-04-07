<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\UserCheckout;
use App\Models\Category;
use App\Models\Product;
use App\Models\Variation;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedStore(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        $this->seed(DemoStoreSeeder::class);
    }

    public function test_add_to_cart_returns_json_for_ajax_requests(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        $variation = Variation::query()->firstOrFail();

        $response = $this->get('/cart?item=' . $variation->id . '&qty=1', [
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
        $user = User::query()->where('username', 'demo')->firstOrFail();

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
        $response->assertSee('Account Settings', false);
    }

    public function test_guest_checkout_session_can_view_order_detail(): void
    {
        $this->seedStore();
        $checkout = UserCheckout::query()->where('email', 'demo@sparesoko.test')->firstOrFail();
        $order = Order::query()->where('user_checkout_id', $checkout->id)->firstOrFail();

        $response = $this->withSession(['user_checkout_id' => $checkout->id])->get('/orders/' . $order->id);

        $response->assertOk();
        $response->assertSee('Order Detail', false);
    }

    public function test_staff_user_can_open_backoffice_dashboard(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/backoffice');

        $response->assertOk();
        $response->assertSee('Dashboard', false);
    }

    public function test_staff_user_is_redirected_away_from_storefront_routes(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/products');

        $response->assertRedirect('/backoffice');
    }

    public function test_staff_login_redirects_to_backoffice_dashboard(): void
    {
        $this->seedStore();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password123',
            'next' => '/products',
        ]);

        $response->assertRedirect('/backoffice');
    }

    public function test_admin_product_update_changes_user_facing_product_data(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $product = Product::query()->where('title', 'Battery Terminal Clamp')->firstOrFail();

        $response = $this->actingAs($admin)->post(
            '/backoffice/products/' . $product->id . '/edit',
            [
                'title' => 'Battery Terminal Clamp',
                'description' => 'Clamp baterai revisi admin.',
                'sku' => 'BTC-NEW-777',
                'oem_number' => 'OEM-777',
                'brand_name' => 'Bosch Update',
                'brand_type' => 'Aftermarket',
                'warranty_label' => 'Garansi Admin 14 Hari',
                'rating' => '4.2',
                'price' => '14.50',
                'default_category_id' => (string) $product->default_category_id,
                'categories' => [(string) $product->default_category_id],
                'compatibility_entries' => [
                    ['vehicle_name' => 'Suzuki Ertiga', 'year_start' => '2018', 'year_end' => '2022'],
                    ['vehicle_name' => 'Toyota Avanza', 'year_start' => '2019', 'year_end' => '2023'],
                ],
                'specification_entries' => [
                    ['label' => 'Bahan', 'value' => 'Aluminium'],
                    ['label' => 'Tegangan', 'value' => '12V'],
                ],
                'variation_entries' => [
                    ['title' => 'Positive Clamp', 'price' => '14.50', 'sale_price' => '', 'inventory' => '9'],
                    ['title' => 'Negative Clamp', 'price' => '15.00', 'sale_price' => '13.50', 'inventory' => '5'],
                ],
                'image_entries' => [
                    ['image_path' => 'theme/img/products/battery-terminal-clamp.jpg', 'alt_text' => 'Clamp utama'],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Clamp kedua'],
                ],
                'active' => '1',
            ],
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $product->refresh();
        $updatedVariation = Variation::query()->where('product_id', $product->id)->where('title', 'Negative Clamp')->firstOrFail();

        $this->assertSame('BTC-NEW-777', $product->sku);
        $this->assertSame('Bosch Update', $product->brand_name);
        $this->assertSame('Aftermarket', $product->brand_type);
        $this->assertSame('Garansi Admin 14 Hari', $product->warranty_label);
        $this->assertSame(5, $updatedVariation->inventory);
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
        $storefrontResponse->assertSee('Stok Menipis (9 unit)', false);
    }

    public function test_admin_can_create_edit_and_delete_category_via_modal_endpoints(): void
    {
        $this->seedStore();
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $category = Category::query()->where('slug', 'brakes')->firstOrFail();

        $createModal = $this->actingAs($admin)->get('/backoffice/categories/create', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $createModal->assertOk();
        $createModal->assertJsonStructure(['html']);

        $createResponse = $this->actingAs($admin)->post('/backoffice/categories/create', [
            'title' => 'Cooling',
            'slug' => 'cooling',
            'description' => 'Cooling parts',
            'active' => '1',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $createResponse->assertOk();
        $createResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('categories', ['slug' => 'cooling', 'title' => 'Cooling']);

        $editModal = $this->actingAs($admin)->get('/backoffice/categories/' . $category->id . '/edit', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $editModal->assertOk();
        $editModal->assertJsonStructure(['html']);

        $editResponse = $this->actingAs($admin)->post('/backoffice/categories/' . $category->id . '/edit', [
            'title' => 'Brake Systems',
            'slug' => 'brakes',
            'description' => 'Updated description',
            'active' => '1',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $editResponse->assertOk();
        $editResponse->assertJson(['success' => true]);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'title' => 'Brake Systems']);

        $deleteModal = $this->actingAs($admin)->get('/backoffice/categories/' . $category->id . '/delete', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $deleteModal->assertOk();
        $deleteModal->assertJsonStructure(['html']);

        $deleteResponse = $this->actingAs($admin)->post('/backoffice/categories/' . $category->id . '/delete', [], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $deleteResponse->assertOk();
        $deleteResponse->assertJson(['success' => true]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_checkout_reduces_inventory_for_purchased_variations_only(): void
    {
        $this->seedStore();
        $customer = User::query()->where('username', 'demo')->firstOrFail();
        $order = Order::query()->with('cart.cartItems.item')->firstOrFail();
        $order->update(['payment_method' => 'cod']);
        $cartItems = $order->cart->cartItems->values();

        $this->assertGreaterThanOrEqual(2, $cartItems->count());

        $firstVariation = Variation::query()->findOrFail($cartItems[0]->variation_id);
        $secondVariation = Variation::query()->findOrFail($cartItems[1]->variation_id);
        $untouchedVariation = Variation::query()
            ->whereNotIn('id', [$firstVariation->id, $secondVariation->id])
            ->firstOrFail();

        $firstBefore = $firstVariation->inventory;
        $secondBefore = $secondVariation->inventory;
        $untouchedBefore = $untouchedVariation->inventory;

        $response = $this->actingAs($customer)
            ->withSession([
                'cart_id' => $order->cart_id,
                'order_id' => $order->id,
                'user_checkout_id' => $order->user_checkout_id,
            ])
            ->post('/checkout/final');

        $response->assertRedirect('/orders/' . $order->id);

        $this->assertSame('created', $order->fresh()->status);
        $this->assertSame($firstBefore - $cartItems[0]->quantity, $firstVariation->fresh()->inventory);
        $this->assertSame($secondBefore - $cartItems[1]->quantity, $secondVariation->fresh()->inventory);
        $this->assertSame($untouchedBefore, $untouchedVariation->fresh()->inventory);
    }

    public function test_cod_checkout_creates_order_without_marking_it_paid(): void
    {
        $this->seedStore();
        $customer = User::query()->where('username', 'demo')->firstOrFail();
        $order = Order::query()->with('cart.cartItems.item')->firstOrFail();
        $order->update(['payment_method' => 'cod']);

        $cartItems = $order->cart->cartItems->values();
        $variation = Variation::query()->findOrFail($cartItems[0]->variation_id);
        $before = $variation->inventory;

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
        $this->assertSame($before - $cartItems[0]->quantity, $variation->fresh()->inventory);
    }

    public function test_prepaid_checkout_requires_payment_gateway_when_not_available(): void
    {
        $this->seedStore();
        $customer = User::query()->where('username', 'demo')->firstOrFail();
        $order = Order::query()->with('cart.cartItems.item')->firstOrFail();
        $order->update(['payment_method' => 'prepaid']);

        $variation = Variation::query()->findOrFail($order->cart->cartItems->firstOrFail()->variation_id);
        $before = $variation->inventory;

        $response = $this->actingAs($customer)
            ->withSession([
                'cart_id' => $order->cart_id,
                'order_id' => $order->id,
                'user_checkout_id' => $order->user_checkout_id,
            ])
            ->post('/checkout/final');

        $response->assertRedirect('/checkout');
        $this->assertSame('created', $order->fresh()->status);
        $this->assertSame($before, $variation->fresh()->inventory);
    }
}
