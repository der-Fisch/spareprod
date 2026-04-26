# INTRODUCTION

Dokumen ini menjelaskan cara menjalankan aplikasi `Spare Soko` dari awal sampai siap dipakai, dengan database `MySQL`.

## 1. Kebutuhan Awal

Pastikan perangkat sudah memiliki:

- `PHP 8.3` atau versi yang kompatibel dengan proyek ini
- `Composer`
- `Node.js` dan `npm`
- `MySQL 8+` atau MariaDB yang kompatibel
- ekstensi PHP `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`

## 2. Masuk ke Folder Proyek

Buka terminal di folder proyek:

```powershell
cd C:\Users\User\33\Spareprod\laravel
```

## 3. Install Dependensi Backend dan Frontend

Jalankan:

```powershell
composer install
npm install
```

## 4. Buat Database MySQL

Masuk ke MySQL:

```sql
mysql -u root -p
```

Lalu jalankan query berikut:

```sql
CREATE DATABASE spare_soko
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Jika ingin memakai user database khusus, jalankan juga:

```sql
CREATE USER 'spare_soko_user'@'localhost' IDENTIFIED BY 'passwordku123';
GRANT ALL PRIVILEGES ON spare_soko.* TO 'spare_soko_user'@'localhost';
FLUSH PRIVILEGES;
```

Jika memakai `root` lokal dari Laragon atau XAMPP, bagian pembuatan user ini boleh dilewati.

## 5. Siapkan File Environment

Salin file `.env.example` menjadi `.env`:

```powershell
Copy-Item .env.example .env
```

Lalu ubah isi bagian database di file `.env` menjadi MySQL, contohnya:

```env
APP_NAME="Spare Soko"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spare_soko
DB_USERNAME=root
DB_PASSWORD=
```

Jika Anda memakai user database khusus, sesuaikan:

```env
DB_USERNAME=spare_soko_user
DB_PASSWORD=passwordku123
```

## 6. Generate Application Key

Jalankan:

```powershell
php artisan key:generate
```

## 7. Jalankan Migrasi dan Seeder

Perintah ini akan:

- membuat seluruh tabel terbaru
- membuat relasi database
- mengisi data awal seperti admin, customer, kategori, brand, produk, keranjang, dan sample order

Jalankan:

```powershell
php artisan migrate --seed
```

Jika sebelumnya database sudah pernah terisi dan ingin diulang dari nol:

```powershell
php artisan migrate:fresh --seed
```

## 8. Data Awal yang Akan Dibuat

Seeder akan membuat akun bawaan berikut:

### Admin

- Username: `admin`
- Email: `admin@sparesoko.test`
- Password: `password123`

### Customer

- Username: `raka.saputra`
- Email: `raka@sparesoko.test`
- Password: `password123`

Seeder juga akan membuat:

- data kategori
- data brand
- data produk
- data variasi produk
- data gambar produk
- data alamat customer
- data cart
- sample order dengan metode `cod`

## 9. Jalankan Aplikasi

Ada dua cara.

### Opsi A: Jalankan manual per terminal

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm run dev
```

### Opsi B: Jalankan sekaligus

```powershell
composer run dev
```

Perintah ini akan menjalankan:

- server Laravel
- queue listener
- log watcher
- Vite frontend

## 10. Akses Aplikasi

Setelah server aktif, buka:

```text
http://127.0.0.1:8000
```

## 11. Login ke Aplikasi

Untuk login admin:

- Username: `admin`
- Password: `password123`

Untuk login customer:

- Username: `raka.saputra`
- Password: `password123`

## 12. Struktur Database Inti yang Sudah Dibuat

Migrasi saat ini sudah mencakup tabel utama berikut:

- `users`
- `brand`
- `categories`
- `products`
- `orders`

Tabel pendukung untuk kebutuhan aplikasi:

- `account_profiles`
- `user_checkouts`
- `user_addresses`
- `carts`
- `cart_items`
- `variations`
- `product_compatibilities`
- `product_specifications`
- `product_images`
- `order_items`
- `category_product`

## 13. Perintah Penting Tambahan

Reset cache konfigurasi:

```powershell
php artisan optimize:clear
```

Menjalankan test:

```powershell
php artisan test
```

Build aset frontend untuk produksi:

```powershell
npm run build
```

## 14. Troubleshooting

### Jika muncul error koneksi database

Periksa:

- `DB_CONNECTION=mysql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Lalu bersihkan cache konfigurasi:

```powershell
php artisan optimize:clear
```

### Jika muncul error `could not find driver`

Berarti ekstensi `pdo_mysql` belum aktif di PHP. Aktifkan `pdo_mysql` pada `php.ini`, lalu restart web server atau terminal.

### Jika tabel belum terbentuk sempurna

Jalankan ulang dari nol:

```powershell
php artisan migrate:fresh --seed
```

## 15. Urutan Paling Aman dari Nol

Jika ingin mengikuti urutan paling aman, jalankan langkah berikut secara berurutan:

```powershell
cd C:\Users\User\33\Spareprod\laravel
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
composer run dev
```

Sebelum menjalankan `migrate:fresh --seed`, pastikan database `spare_soko` di MySQL sudah dibuat dan konfigurasi `.env` sudah diarahkan ke MySQL.
