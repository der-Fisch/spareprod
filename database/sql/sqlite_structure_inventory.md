# SQLite Structure Inventory

Sumber data aktual ada di `database/database.sqlite`.

## Tabel inti bisnis

| Tabel | Rows | Keterangan inti |
| --- | ---: | --- |
| `users` | 2 | PK `id` integer, akun aplikasi, dipakai oleh `account_profiles`, `carts`, `user_checkouts`, `user_payment_methods`, dan `orders.user_id`. |
| `categories` | 3 | PK `id` integer, kategori katalog. Kolom minimum kompatibilitas: `nama_kategori`. |
| `brand` | 5 | PK `id` varchar, brand produk. |
| `products` | 6 | PK `id` integer. Kolom minimum kompatibilitas: `kode_produk`, `nama_produk`, `tipe_kendaraan`, `kategori_id`, `harga`, `stok`, `gambar`, `brand_id`. |
| `variations` | 10 | Varian harga/stok per produk, FK ke `products`. |
| `category_product` | 6 | Pivot kategori-produk. |
| `product_compatibilities` | 15 | Daftar kendaraan kompatibel per produk. |
| `product_specifications` | 24 | Spesifikasi teknis per produk. |
| `product_images` | 18 | Gambar produk per produk. |
| `carts` | 1 | Keranjang user. |
| `cart_items` | 2 | Isi keranjang, FK ke `carts` dan `variations`. |
| `user_checkouts` | 1 | Profil checkout, FK opsional ke `users`. |
| `user_addresses` | 1 | Alamat checkout, FK ke `user_checkouts`. |
| `user_payment_methods` | 1 | Metode bayar tersimpan, FK ke `users`. |
| `orders` | 1 | PK `id` integer. Kolom minimum kompatibilitas: `id_pembelian`, `user_id`, `kode_produk`, `jumlah`, `total_bayar`, `tanggal_transaksi`. |
| `order_items` | 0 | Detail item order, FK ke `orders` dan `variations`. |
| `account_profiles` | 2 | Profil akun tambahan, FK ke `users`. |

## Tabel runtime Laravel

`cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `sessions`.

## Gap terhadap target MySQL minimum

1. Aplikasi aktif memakai `categories` dan `orders`, tetapi target MySQL minimum meminta `kategori` dan `pembelian`.
2. PK SQLite untuk `users`/`categories`/`orders` berbentuk integer, sedangkan target minimum meminta `VARCHAR(50)`.
3. Nilai role user di app saat ini bertipe bebas (`customer`, `admin`), sedangkan target minimum meminta enum `admin/user`.
4. Nilai status order di app saat ini memakai istilah seperti `created`, `paid`, `shipped`, `refunded`, sedangkan target minimum meminta enum `pending/dibayar/dikirim/selesai/batal`.
5. Harga dan total di SQLite bertipe desimal; pada export minimum MySQL disimpan sebagai `BIGINT` dalam satuan sen agar tidak kehilangan pecahan.

## Artefak migrasi yang ditambahkan

- `database/sql/mysql_minimum_schema.sql`
- `database/sql/mysql_minimum_from_sqlite.sql`
- `database/sqlite_to_mysql_minimum.py`
