<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brand')) {
            Schema::create('brand', function (Blueprint $table) {
                $table->string('id', 60)->primary();
                $table->string('nama_brand');
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('customer')->after('password');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'nama_kategori')) {
                $table->string('nama_kategori')->nullable()->after('title');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'kode_produk')) {
                $table->string('kode_produk', 80)->nullable()->after('id')->unique();
            }
            if (! Schema::hasColumn('products', 'nama_produk')) {
                $table->string('nama_produk')->nullable()->after('title');
            }
            if (! Schema::hasColumn('products', 'tipe_kendaraan')) {
                $table->string('tipe_kendaraan')->nullable()->after('nama_produk');
            }
            if (! Schema::hasColumn('products', 'kategori_id')) {
                $table->foreignId('kategori_id')->nullable()->after('default_category_id')->constrained('categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'harga')) {
                $table->decimal('harga', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('products', 'stok')) {
                $table->integer('stok')->nullable()->after('harga');
            }
            if (! Schema::hasColumn('products', 'gambar')) {
                $table->string('gambar')->nullable()->after('stok');
            }
            if (! Schema::hasColumn('products', 'brand_id')) {
                $table->string('brand_id', 60)->nullable()->after('brand_name');
                $table->foreign('brand_id')->references('id')->on('brand')->nullOnDelete();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'id_pembelian')) {
                $table->string('id_pembelian', 60)->nullable()->after('id')->unique();
            }
            if (! Schema::hasColumn('orders', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('cart_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'kode_produk')) {
                $table->string('kode_produk', 80)->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('orders', 'jumlah')) {
                $table->integer('jumlah')->nullable()->after('kode_produk');
            }
            if (! Schema::hasColumn('orders', 'total_bayar')) {
                $table->decimal('total_bayar', 12, 2)->nullable()->after('order_total');
            }
            if (! Schema::hasColumn('orders', 'tanggal_transaksi')) {
                $table->timestamp('tanggal_transaksi')->nullable()->after('created_at');
            }
        });

        $this->backfillUserRoles();
        $this->backfillCategoryNames();
        $this->backfillBrandsAndProducts();
        $this->backfillOrders();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            $columns = collect(['id_pembelian', 'kode_produk', 'jumlah', 'total_bayar', 'tanggal_transaksi'])
                ->filter(fn (string $column) => Schema::hasColumn('orders', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'brand_id')) {
                $table->dropForeign(['brand_id']);
            }
            if (Schema::hasColumn('products', 'kategori_id')) {
                $table->dropConstrainedForeignId('kategori_id');
            }

            $columns = collect(['kode_produk', 'nama_produk', 'tipe_kendaraan', 'harga', 'stok', 'gambar', 'brand_id'])
                ->filter(fn (string $column) => Schema::hasColumn('products', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'nama_kategori')) {
                $table->dropColumn('nama_kategori');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::dropIfExists('brand');
    }

    protected function backfillUserRoles(): void
    {
        DB::table('users')->orderBy('id')->get()->each(function (object $user): void {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'role' => (bool) $user->is_staff ? 'admin' : 'customer',
                ]);
        });
    }

    protected function backfillCategoryNames(): void
    {
        DB::table('categories')->orderBy('id')->get()->each(function (object $category): void {
            DB::table('categories')
                ->where('id', $category->id)
                ->update([
                    'nama_kategori' => $category->nama_kategori ?: $category->title,
                ]);
        });
    }

    protected function backfillBrandsAndProducts(): void
    {
        $brandMap = [];

        DB::table('products')
            ->select('brand_name')
            ->whereNotNull('brand_name')
            ->distinct()
            ->pluck('brand_name')
            ->filter()
            ->each(function (string $brandName) use (&$brandMap): void {
                $brandId = $this->makeBrandId($brandName, $brandMap);
                $brandMap[$brandName] = $brandId;

                DB::table('brand')->updateOrInsert(
                    ['id' => $brandId],
                    [
                        'nama_brand' => $brandName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });

        DB::table('products')->orderBy('id')->get()->each(function (object $product) use ($brandMap): void {
            $firstCompatibility = DB::table('product_compatibilities')
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('vehicle_name');

            $firstImage = DB::table('product_images')
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('image_path');

            $stock = (int) (DB::table('variations')
                ->where('product_id', $product->id)
                ->sum('inventory') ?? 0);

            $kodeProduk = $product->kode_produk ?: $product->sku ?: 'PRD-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
            $namaProduk = $product->nama_produk ?: $product->title;
            $kategoriId = $product->kategori_id ?: $product->default_category_id;
            $harga = $product->harga ?? $product->price;
            $brandId = $product->brand_id ?: ($brandMap[$product->brand_name] ?? null);

            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'kode_produk' => $kodeProduk,
                    'nama_produk' => $namaProduk,
                    'tipe_kendaraan' => $product->tipe_kendaraan ?: ($firstCompatibility ?: 'Universal'),
                    'kategori_id' => $kategoriId,
                    'harga' => $harga,
                    'stok' => $product->stok ?? $stock,
                    'gambar' => $product->gambar ?: $firstImage,
                    'brand_id' => $brandId,
                ]);
        });
    }

    protected function backfillOrders(): void
    {
        DB::table('orders')->orderBy('id')->get()->each(function (object $order): void {
            $checkout = $order->user_checkout_id
                ? DB::table('user_checkouts')->where('id', $order->user_checkout_id)->first()
                : null;

            $firstOrderItem = DB::table('order_items')
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->first();

            $jumlah = (int) DB::table('order_items')->where('order_id', $order->id)->sum('quantity');
            $kodeProduk = null;

            if ($firstOrderItem?->variation_id) {
                $kodeProduk = DB::table('variations')
                    ->join('products', 'products.id', '=', 'variations.product_id')
                    ->where('variations.id', $firstOrderItem->variation_id)
                    ->value('products.kode_produk');
            }

            if (! $kodeProduk || $jumlah === 0) {
                $cartItems = DB::table('cart_items')->where('cart_id', $order->cart_id);
                $jumlah = $jumlah ?: (int) $cartItems->sum('quantity');
                $firstCartItem = DB::table('cart_items')->where('cart_id', $order->cart_id)->orderBy('id')->first();

                if ($firstCartItem?->variation_id) {
                    $kodeProduk = DB::table('variations')
                        ->join('products', 'products.id', '=', 'variations.product_id')
                        ->where('variations.id', $firstCartItem->variation_id)
                        ->value('products.kode_produk');
                }
            }

            $idPembelian = $order->id_pembelian ?: $order->order_id ?: 'PBL-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'id_pembelian' => $idPembelian,
                    'user_id' => $order->user_id ?: ($checkout->user_id ?? null),
                    'kode_produk' => $order->kode_produk ?: $kodeProduk,
                    'jumlah' => $order->jumlah ?? $jumlah,
                    'total_bayar' => $order->total_bayar ?? $order->order_total,
                    'tanggal_transaksi' => $order->tanggal_transaksi ?: $order->created_at,
                ]);
        });
    }

    protected function makeBrandId(string $brandName, array $brandMap): string
    {
        $baseId = Str::slug($brandName, '_');
        $baseId = $baseId !== '' ? $baseId : 'brand';
        $brandId = $baseId;
        $suffix = 1;

        while (in_array($brandId, $brandMap, true) || DB::table('brand')->where('id', $brandId)->exists()) {
            $suffix++;
            $brandId = $baseId . '_' . $suffix;
        }

        return $brandId;
    }
};
