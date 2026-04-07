<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    $targetTables = collect(Schema::connection('mysql')->getTableListing());

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
    } finally {
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    $this->newLine();
    $this->info('Migrasi data utama sqlite ke mysql selesai.');
    $this->line('Verifikasi dengan file database/sql/mysql_primary_data_audit.sql sebelum mengubah DB_CONNECTION=mysql.');
    $this->line('Langkah berikutnya: ubah DB_CONNECTION=mysql di .env lalu jalankan php artisan config:clear');

    return 0;
})->purpose('Copy primary application data from SQLite to MySQL');
