<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:sqlite-to-mysql
    {--fresh : Reset and migrate the MySQL schema before copying data}
    {--with-runtime : Also copy session, cache, queue, and token tables}', function () {
    $sqliteDatabase = config('database.connections.sqlite.database');
    $mysqlDatabase = config('database.connections.mysql.database');

    if (! $sqliteDatabase || ! file_exists($sqliteDatabase)) {
        $this->error('SQLite source database tidak ditemukan. Periksa konfigurasi DB sqlite Anda.');
        return 1;
    }

    if (! $mysqlDatabase) {
        $this->error('Konfigurasi MySQL belum lengkap. Isi DB_DATABASE, DB_USERNAME, dan DB_PASSWORD untuk koneksi mysql.');
        return 1;
    }

    try {
        DB::connection('mysql')->getPdo();
    } catch (\Throwable $exception) {
        $this->error('Koneksi MySQL gagal: ' . $exception->getMessage());
        return 1;
    }

    if ($this->option('fresh')) {
        $this->info('Menjalankan migrate:fresh pada koneksi mysql...');
        $this->call('migrate:fresh', [
            '--database' => 'mysql',
            '--force' => true,
        ]);
    }

    $sourceTables = collect(DB::connection('sqlite')->select("
        SELECT name
        FROM sqlite_master
        WHERE type = 'table'
          AND name NOT LIKE 'sqlite_%'
        ORDER BY name
    "))->pluck('name');

    $targetTables = collect(Schema::connection('mysql')->getTableListing())
        ->map(function (string $table): string {
            $segments = explode('.', $table);

            return end($segments) ?: $table;
        })
        ->unique()
        ->values();

    $primaryTables = [
        'brand',
        'categories',
        'products',
        'category_product',
        'product_compatibilities',
        'product_specifications',
        'product_images',
        'variations',
        'users',
        'password_reset_tokens',
        'account_profiles',
        'user_checkouts',
        'user_addresses',
        'user_payment_methods',
        'carts',
        'cart_items',
        'orders',
        'order_items',
    ];

    $runtimeTables = [
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    $preferredOrder = $this->option('with-runtime')
        ? array_merge($primaryTables, $runtimeTables)
        : $primaryTables;

    $allowedTables = collect($preferredOrder);

    $tables = $allowedTables
        ->intersect($sourceTables)
        ->intersect($targetTables)
        ->values();

    if ($tables->isEmpty()) {
        $this->warn('Tidak ada tabel utama yang bisa disalin dari sqlite ke mysql.');
        return 0;
    }

    $skippedTables = $sourceTables
        ->intersect($targetTables)
        ->reject(fn (string $table) => $allowedTables->contains($table) || $table === 'migrations')
        ->values();

    $this->info('Tabel utama yang akan disalin:');
    foreach ($tables as $table) {
        $this->line(' - ' . $table);
    }

    if ($skippedTables->isNotEmpty()) {
        $this->newLine();
        $this->warn('Tabel yang dilewati: ' . $skippedTables->implode(', '));
        if (! $this->option('with-runtime')) {
            $this->line('Gunakan opsi --with-runtime jika memang ingin menyalin tabel runtime juga.');
        }
        $this->newLine();
    }

    DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0');

    try {
        foreach ($tables as $table) {
            $rowObjects = DB::connection('sqlite')->table($table)->get();
            $rows = $rowObjects->map(fn ($row) => (array) $row)->all();

            $this->line("Menyalin tabel <info>{$table}</info> (" . count($rows) . ' baris)...');

            DB::connection('mysql')->table($table)->truncate();

            if ($rows === []) {
                continue;
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::connection('mysql')->table($table)->insert($chunk);
            }
        }

        DB::connection('mysql')->table('users')->orderBy('id')->get()->each(function (object $user): void {
            DB::connection('mysql')->table('users')
                ->where('id', $user->id)
                ->update([
                    'role' => (bool) $user->is_staff ? 'admin' : 'customer',
                ]);
        });

        DB::connection('mysql')->table('categories')->orderBy('id')->get()->each(function (object $category): void {
            DB::connection('mysql')->table('categories')
                ->where('id', $category->id)
                ->update([
                    'nama_kategori' => $category->nama_kategori ?: $category->title,
                ]);
        });

        $brandMap = [];

        DB::connection('mysql')->table('products')
            ->select('brand_name')
            ->whereNotNull('brand_name')
            ->distinct()
            ->pluck('brand_name')
            ->filter()
            ->each(function (string $brandName) use (&$brandMap): void {
                $baseId = Str::slug($brandName, '_');
                $baseId = $baseId !== '' ? $baseId : 'brand';
                $brandId = $baseId;
                $suffix = 1;

                while (
                    in_array($brandId, $brandMap, true)
                    || DB::connection('mysql')->table('brand')->where('id', $brandId)->where('nama_brand', '!=', $brandName)->exists()
                ) {
                    $suffix++;
                    $brandId = $baseId . '_' . $suffix;
                }

                $brandMap[$brandName] = $brandId;

                DB::connection('mysql')->table('brand')->updateOrInsert(
                    ['id' => $brandId],
                    [
                        'nama_brand' => $brandName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });

        DB::connection('mysql')->table('products')->orderBy('id')->get()->each(function (object $product) use ($brandMap): void {
            $firstCompatibility = DB::connection('mysql')->table('product_compatibilities')
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('vehicle_name');

            $firstImage = DB::connection('mysql')->table('product_images')
                ->where('product_id', $product->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('image_path');

            $stock = (int) (DB::connection('mysql')->table('variations')
                ->where('product_id', $product->id)
                ->sum('inventory') ?? 0);

            $kodeProduk = $product->kode_produk ?: $product->sku ?: 'PRD-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
            $namaProduk = $product->nama_produk ?: $product->title;
            $kategoriId = $product->kategori_id ?: $product->default_category_id;
            $harga = $product->harga ?? $product->price;
            $brandId = $product->brand_id ?: ($brandMap[$product->brand_name] ?? null);

            DB::connection('mysql')->table('products')
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

        DB::connection('mysql')->table('orders')->orderBy('id')->get()->each(function (object $order): void {
            $checkout = $order->user_checkout_id
                ? DB::connection('mysql')->table('user_checkouts')->where('id', $order->user_checkout_id)->first()
                : null;

            $firstOrderItem = DB::connection('mysql')->table('order_items')
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->first();

            $jumlah = (int) DB::connection('mysql')->table('order_items')->where('order_id', $order->id)->sum('quantity');
            $kodeProduk = null;

            if ($firstOrderItem?->variation_id) {
                $kodeProduk = DB::connection('mysql')->table('variations')
                    ->join('products', 'products.id', '=', 'variations.product_id')
                    ->where('variations.id', $firstOrderItem->variation_id)
                    ->value('products.kode_produk');
            }

            if (! $kodeProduk || $jumlah === 0) {
                $jumlah = $jumlah ?: (int) DB::connection('mysql')->table('cart_items')->where('cart_id', $order->cart_id)->sum('quantity');
                $firstCartItem = DB::connection('mysql')->table('cart_items')->where('cart_id', $order->cart_id)->orderBy('id')->first();

                if ($firstCartItem?->variation_id) {
                    $kodeProduk = DB::connection('mysql')->table('variations')
                        ->join('products', 'products.id', '=', 'variations.product_id')
                        ->where('variations.id', $firstCartItem->variation_id)
                        ->value('products.kode_produk');
                }
            }

            $idPembelian = $order->id_pembelian ?: $order->order_id ?: 'PBL-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);

            DB::connection('mysql')->table('orders')
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
    } finally {
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    $this->newLine();
    $this->info('Migrasi data utama sqlite ke mysql selesai.');
    $this->line('Verifikasi dengan file database/sql/mysql_primary_data_audit.sql sebelum mengubah DB_CONNECTION=mysql.');
    $this->line('Langkah berikutnya: ubah DB_CONNECTION=mysql di .env lalu jalankan php artisan config:clear');

    return 0;
})->purpose('Copy primary application data from SQLite to MySQL');
