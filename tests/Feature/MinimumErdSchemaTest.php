<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MinimumErdSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimum_erd_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('brand'));
        $this->assertTrue(Schema::hasColumns('users', ['role']));
        $this->assertTrue(Schema::hasColumns('categories', ['nama_kategori']));
        $this->assertTrue(Schema::hasColumns('products', [
            'kode_produk',
            'nama_produk',
            'tipe_kendaraan',
            'kategori_id',
            'harga',
            'stok',
            'gambar',
            'brand_id',
        ]));
        $this->assertTrue(Schema::hasColumns('orders', [
            'id_pembelian',
            'user_id',
            'kode_produk',
            'jumlah',
            'total_bayar',
            'tanggal_transaksi',
        ]));
    }

    public function test_seeded_data_is_synced_to_minimum_erd_fields(): void
    {
        $this->seed(DemoCatalogSeeder::class);
        $this->seed(DemoStoreSeeder::class);

        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $customer = User::query()->where('username', 'demo')->firstOrFail();
        $product = Product::query()->where('sku', 'BTC-12V-009')->firstOrFail();
        $order = Order::query()->where('order_id', 'SSK-1001')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertSame('customer', $customer->role);

        $this->assertSame('BTC-12V-009', $product->kode_produk);
        $this->assertSame('Battery Terminal Clamp', $product->nama_produk);
        $this->assertNotNull($product->brand_id);
        $this->assertNotNull($product->kategori_id);
        $this->assertNotNull($product->harga);
        $this->assertNotNull($product->stok);
        $this->assertNotNull($product->gambar);

        $this->assertSame('SSK-1001', $order->id_pembelian);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertNotNull($order->kode_produk);
        $this->assertGreaterThan(0, (int) $order->jumlah);
        $this->assertSame((string) $order->order_total, (string) $order->total_bayar);
        $this->assertNotNull($order->tanggal_transaksi);
    }
}
