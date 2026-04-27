# SparePrud

Aplikasi e-commerce sparepart motor sederhana berbasis PHP, MySQL, HTML, CSS, dan sedikit JavaScript.

## Cara Menjalankan

1. Import file SQL `database/spareprud.sql`.
2. Pastikan MySQL aktif dan database dapat diakses lewat:
   - host: `127.0.0.1`
   - port: `3306`
   - user: `root`
   - password: kosong
3. Jalankan PHP built-in server dari root project:

```bash
php -S localhost:8000
```

4. Buka `http://localhost:8000`.

## Akun Demo

- Admin
  - username: `admin`
  - password: `admin123`
- Customer
  - username: `budi`
  - password: `customer123`

## Fitur Minimum

- Login dan logout admin/customer
- Admin kelola data sparepart
- Customer membuat pembelian
- Admin konfirmasi atau tolak pembelian
