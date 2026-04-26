# Spare Soko

Dokumentasi teknis aplikasi `Spare Soko` yang digunakan dan dipelihara di folder ini.

## 1. Nama Aplikasi

- `Spare Soko`
- Nama repository/workspace lokal: `Spareprod Laravel`

Nama `Spare Soko` muncul konsisten pada title halaman Blade, sidebar, navbar, invoice, dan identitas aplikasi.

## 2. Deskripsi Aplikasi

`Spare Soko` adalah aplikasi web e-commerce spare part kendaraan berbasis Laravel yang menggabungkan:

- `storefront customer` untuk browsing katalog, cart, checkout, dan order history
- `admin admin/staff` untuk mengelola kategori, produk, dan status order

### Tujuan aplikasi

Aplikasi ini dirancang untuk menyediakan katalog spare part yang lebih terstruktur, mudah dicari, dan siap dipakai untuk alur pembelian nyata. Sistem juga mempertahankan kompatibilitas dengan struktur data minimum bergaya ERP lama melalui kolom mirror seperti:

- `title` <-> `nama_produk`
- `sku` <-> `kode_produk`
- `default_category_id` <-> `kategori_id`
- `price` <-> `harga`
- `order_id` <-> `id_pembelian`

### Target pengguna

- `customer`: mencari spare part, melihat detail teknis, menambahkan ke cart, checkout, dan melihat order
- `admin/staff`: mengelola katalog, kategori, gambar produk, spesifikasi, kompatibilitas, dan status order

### Masalah yang diselesaikan

- katalog spare part yang sebelumnya belum tertata kini memiliki struktur kategori, detail teknis, kompatibilitas kendaraan, dan gambar
- proses belanja dibuat end-to-end dari katalog sampai order detail
- area admin dan customer dipisahkan, tetapi tetap berada dalam satu codebase
- data aplikasi modern tetap bisa dipetakan ke struktur minimum ERP

### Workflow utama aplikasi

1. Pengunjung membuka landing page.
2. Pengunjung melihat katalog dan detail produk.
3. Produk ditambahkan ke cart.
4. User memilih item tertentu yang ingin dibeli.
5. User checkout sebagai guest atau login.
6. User memilih alamat pengiriman.
7. Sistem membuat snapshot order, menghitung total, dan memproses finalisasi.
8. Admin mengelola data dari admin.

## 3. Bahasa Pemrograman

Bahasa yang digunakan di project ini:

- `PHP`
- `Blade`
- `JavaScript`
- `CSS`
- `SQL`
- `JSON`
- `XML`
- `Markdown`

## 4. Framework & Libraries

### Framework utama

- `Laravel 13`
  - menangani routing, middleware, auth session, Eloquent ORM, migration, seeder, queue, logging, testing, dan command artisan

### Library dan tool penting

- `Eloquent ORM`
  - akses database dan relasi data memakai pattern `Active Record`
- `Blade`
  - server-side rendering untuk halaman publik dan admin
- `Bootstrap`
  - basis layout dan komponen visual utama
- `jQuery`
  - AJAX cart, modal admin, live search, widget form dinamis, dan interaksi UI lainnya
- `SweetAlert2`
  - alert interaktif untuk feedback aksi user
- `Font Awesome 4.3`
  - ikon UI
- `Vite 8`
  - bundler frontend modern yang sudah disiapkan
- `Tailwind CSS 4`
  - tersedia di `resources/css/app.css`, tetapi belum menjadi jalur styling UI aktif utama
- `Axios`
  - tersedia di bootstrap JS, tetapi implementasi UI aktif lebih dominan memakai `jQuery.ajax`
- `PHPUnit 12`
  - test suite otomatis
- `laravel/pint`
  - formatter kode PHP
- `laravel/pail`
  - live tail log saat development
- `concurrently`
  - menjalankan server, queue, log tail, dan Vite secara paralel

## 5. Arsitektur Aplikasi

### Pola arsitektur

Aplikasi ini menggunakan:

- `Laravel monolith`
- pendekatan `MVC`
- implementasi yang cenderung `controller-centric`

Artinya, aplikasi tidak dipisah menjadi microservices. Frontend publik, admin, auth, checkout, dan data layer semua berada dalam satu project Laravel.

### Interaksi frontend dan backend

- frontend publik dan admin dirender oleh Blade
- beberapa route web juga dipakai sebagai endpoint AJAX internal
- respons dapat berupa:
  - full HTML page
  - partial HTML untuk modal/table
  - JSON untuk cart dan aksi AJAX tertentu

Project ini tidak memakai `routes/api.php`; interaksi internal tetap berada di `web` route.

### Data flow aplikasi

1. Request masuk melalui `public/index.php`
2. Laravel dibootstrap oleh `bootstrap/app.php`
3. Router membaca definisi di `routes/web.php`
4. Middleware `redirect_staff_to_admin` memisahkan flow staff/admin dari customer
5. Controller mengambil data melalui model Eloquent
6. Model Eloquent menjalankan relasi, accessor, dan event hook domain
7. View Blade merender output HTML
8. JavaScript pada `public/theme/js/custom.js` mengirim request AJAX ke route yang sama untuk update UI tanpa reload penuh

## 6. Fitur Utama

### Storefront

- `Landing page`
  - menampilkan featured product dan preview produk aktif
- `Katalog produk`
  - filter berdasarkan kata kunci, kategori, harga minimum, dan harga maksimum
- `Detail produk`
  - menampilkan SKU, OEM, merek, tipe brand, spesifikasi teknis, stok, rating, garansi, galeri gambar, dan produk terkait
- `Add to cart via AJAX`
  - produk ditambahkan ke cart tanpa reload halaman
- `Cart dengan seleksi item`
  - user dapat centang item tertentu, centang per brand, atau pilih semua
- `Partial checkout`
  - hanya item yang `is_selected=true` yang ikut checkout
- `Guest checkout`
  - checkout dapat dimulai tanpa login memakai email
- `Authenticated checkout`
  - reuse alamat akun yang sudah tersimpan
- `Riwayat order`
  - daftar order dan detail order customer
- `Invoice modal`
  - invoice order ditampilkan dalam modal

### Account center

- `Biodata akun`
  - username, email, nama depan, nama belakang, nomor HP, WhatsApp, tanggal lahir, gender
- `Daftar alamat`
  - create, update, delete, dan set default address
- `Keamanan akun`
  - ganti password

### Admin

- `Dashboard`
  - KPI produk, kategori, order, revenue rows, recent activity, quick actions
- `Category management`
  - CRUD kategori via modal AJAX
- `Product management`
  - CRUD produk dengan field teknis lengkap
- `Order management`
  - update status order via modal AJAX
- `Generic modal CRUD engine`
  - satu controller mengelola beberapa entity melalui konfigurasi per entity

### Sistem domain penting

- `Order snapshot`
  - item yang dibeli disalin ke `order_items`
- `Inventory deduction`
  - stok hanya dikurangi saat final checkout sukses
- `ERP minimum sync`
  - field modern disinkronkan ke kolom mirror lama
- `SQLite to MySQL migration command`
  - ada command custom untuk copy data utama ke MySQL

## Analisis Struktur Project

```text
laravel/                                                # root aplikasi Laravel untuk storefront + admin Spare Soko
├── .git/                                               # metadata Git repository
├── app/                                                # kode PHP inti aplikasi
│   ├── Http/                                           # layer request/response web
│   │   ├── Controllers/                                # orkestrasi alur bisnis berbasis route
│   │   │   ├── Controller.php                          # base controller kosong bawaan Laravel
│   │   │   ├── HomeController.php                      # landing page; ambil featured product + 3 produk aktif acak
│   │   │   ├── ProductController.php                   # katalog, filter, dan detail produk + related products
│   │   │   ├── CartController.php                      # cart session/auth, add/update/remove item, AJAX count/selection
│   │   │   ├── CheckoutController.php                  # guest checkout, address flow, payment selection, finalisasi order
│   │   │   ├── OrderController.php                     # riwayat order dan detail order customer/guest session
│   │   │   ├── Account/
│   │   │   │   └── AccountSettingsController.php       # tab biodata, alamat, pembayaran, keamanan; beda flow admin vs customer
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php                  # login, register, logout, redirect next, redirect staff ke admin
│   │   │   └── Admin/
│   │   │       └── AdminController.php                 # dashboard admin dan CRUD generik entity categories/products/orders
│   │   └── Middleware/
│   │       └── RedirectStaffToAdmin.php                # paksa user staff keluar dari storefront menuju dashboard admin
│   ├── Models/                                         # model Eloquent + relasi + accessor + hook sinkronisasi
│   │   ├── AccountProfile.php                          # profil tambahan user: WhatsApp, HP, tanggal lahir, gender
│   │   ├── Brand.php                                   # master brand ERP-style; primary key string
│   │   ├── Cart.php                                    # cart dengan subtotal/tax/total dan accessor selected summary
│   │   ├── CartItem.php                                # item cart; hitung line total dan refresh total cart via model hook
│   │   ├── Category.php                                # kategori produk; mirror title <-> nama_kategori
│   │   ├── Order.php                                   # order header; snapshot total, payment label, status label, mirror ERP
│   │   ├── OrderItem.php                               # snapshot item order; menjaga histori transaksi stabil
│   │   ├── Product.php                                 # entitas produk utama; mirror kolom storefront dan ERP minimum
│   │   ├── ProductCompatibility.php                    # daftar kendaraan yang kompatibel dengan produk
│   │   ├── ProductImage.php                            # gambar produk + accessor URL fallback
│   │   ├── ProductSpecification.php                    # spesifikasi teknis produk dalam pasangan label-value
│   │   ├── User.php                                    # autentikasi user, role admin/customer, relasi profile/cart/payment
│   │   ├── UserAddress.php                             # alamat shipping/billing milik checkout profile
│   │   ├── UserCheckout.php                            # profil checkout yang menjembatani guest dan authenticated user
│   │   └── Variation.php                               # varian produk + harga efektif + inventory per varian
│   ├── Providers/
│   │   └── AppServiceProvider.php                      # register helper global dan share `sharedCartCount` ke semua view
│   └── Support/
│       └── helpers.php                                 # helper `rupiah`, `rupiah_catalog`, `avatar_initials`, `resolve_path_value`
├── bootstrap/
│   ├── app.php                                         # bootstrap Laravel 13; routing, alias middleware, health endpoint `/up`
│   └── cache/                                          # cache bootstrap/compiled framework
├── config/                                             # konfigurasi runtime Laravel
│   ├── app.php                                         # konfigurasi global framework, locale, timezone, provider
│   ├── auth.php                                        # guard `web`, provider Eloquent `User`, reset password config
│   ├── cache.php                                       # cache store; env aktif memakai database cache
│   ├── database.php                                    # koneksi SQLite/MySQL/MariaDB/PgSQL/SQL Server
│   ├── filesystems.php                                 # disk `local`, `public`, `s3`; upload admin saat ini tetap ke `public/`
│   ├── logging.php                                     # log channel default `stack -> single`
│   ├── mail.php                                        # konfigurasi mailer; env aktif memakai `log`
│   ├── queue.php                                       # queue database sebagai default
│   ├── services.php                                    # placeholder integrasi Postmark, Resend, SES, Slack
│   └── session.php                                     # session driver database, cookie, same-site, serialisasi JSON
├── database/                                           # skema, seed, dan audit SQL
│   ├── factories/
│   │   └── UserFactory.php                             # factory user untuk kebutuhan testing/seed Laravel standar
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php    # users, password_reset_tokens, sessions
│   │   ├── 0001_01_01_000001_create_cache_table.php    # cache dan cache_locks berbasis database
│   │   ├── 0001_01_01_000002_create_jobs_table.php     # jobs, job_batches, failed_jobs untuk queue database
│   │   ├── 2026_04_06_000003_create_categories_table.php # tabel kategori produk
│   │   ├── 2026_04_06_000004_create_products_table.php # tabel produk dasar storefront
│   │   ├── 2026_04_06_000005_create_variations_table.php # tabel varian harga/stok per produk
│   │   ├── 2026_04_06_000006_create_category_product_table.php # pivot many-to-many category-product
│   │   ├── 2026_04_06_000007_create_account_profiles_table.php # profil tambahan akun
│   │   ├── 2026_04_06_000008_create_carts_table.php    # cart header dengan tax config
│   │   ├── 2026_04_06_000009_create_cart_items_table.php # item cart dan line total
│   │   ├── 2026_04_06_000010_create_user_checkouts_table.php # profil checkout berbasis email/user
│   │   ├── 2026_04_06_000011_create_user_addresses_table.php # alamat checkout awal
│   │   ├── 2026_04_06_000012_create_orders_table.php   # header order awal
│   │   ├── 2026_04_06_000013_add_spare_part_fields_to_products_table.php # SKU, OEM, brand, garansi, rating, review_count
│   │   ├── 2026_04_06_000014_create_product_compatibilities_table.php # kompatibilitas kendaraan
│   │   ├── 2026_04_06_000015_create_product_specifications_table.php # spesifikasi teknis
│   │   ├── 2026_04_06_000016_backfill_spare_part_product_profiles.php # backfill profil awal spare part + specs + compatibility
│   │   ├── 2026_04_06_000017_create_product_images_table.php # tabel gambar produk
│   │   ├── 2026_04_06_000018_backfill_product_images.php # backfill galeri gambar produk awal
│   │   ├── 2026_04_07_000001_add_payment_method_to_orders_table.php # tambah mode pembayaran order
│   │   ├── 2026_04_07_100000_add_customer_fields_to_account_profiles_table.php # HP, birth_date, gender
│   │   ├── 2026_04_07_100100_add_customer_fields_to_user_addresses_table.php # label, recipient, phone, default flag
│   │   ├── 2026_04_07_110000_add_is_selected_to_cart_items_table.php # centang item cart untuk partial checkout
│   │   ├── 2026_04_07_110100_add_checkout_snapshot_totals_to_orders_table.php # subtotal/tax/items total snapshot
│   │   ├── 2026_04_07_110200_create_order_items_table.php # snapshot item order
│   │   ├── 2026_04_07_130500_align_minimum_erd_structure.php # sinkronisasi ke skema ERP minimum + backfill data
│   │   └── 2026_04_07_140000_remove_shipping_amounts_from_existing_orders.php # set ongkir lama menjadi 0
│   ├── seeders/
│   │   ├── DatabaseSeeder.php                          # memanggil seed katalog dan seed store awal
│   │   ├── CatalogSeeder.php                           # isi kategori, produk, varian, specs, gambar, dan compatibility awal
│   │   └── StoreSeeder.php                             # isi akun admin, akun customer, address, cart, dan order awal
│   └── sql/
│       └── mysql_primary_data_audit.sql                # query audit hasil copy data SQLite ke MySQL
├── public/                                             # document root web server
│   ├── favicon.ico                                     # favicon browser
│   ├── index.php                                       # front controller Laravel
│   ├── robots.txt                                      # aturan crawler
│   ├── theme/                                          # asset UI aktif yang benar-benar dipakai Blade
│   │   ├── css/
│   │   │   ├── bootstrap.min.css                       # CSS Bootstrap
│   │   │   ├── custom.css                              # design token, layout publik/admin, komponen kustom utama
│   │   │   └── navbar-static-top.css                   # styling navbar turunan Bootstrap
│   │   ├── img/
│   │   │   ├── marketing1.jpg                          # gambar fallback/global marketing
│   │   │   ├── mvp_landing_logo.png                    # logo landing/store
│   │   │   └── products/                               # gambar produk katalog
│   │   ├── js/
│   │   │   ├── bootstrap.min.js                        # JavaScript Bootstrap
│   │   │   ├── custom.js                               # helper global frontend lintas halaman
│   │   │   ├── layout-shell.js                         # interaksi layout publik/admin
│   │   │   ├── catalog-page.js                         # filter dan pagination katalog
│   │   │   ├── product-detail-page.js                  # interaksi halaman detail produk
│   │   │   ├── cart-page.js                            # interaksi halaman cart
│   │   │   ├── admin-form-widgets.js                   # widget form admin: multiselect, repeater, preview, currency
│   │   │   ├── admin-entity-page.js                    # modal AJAX dan refresh tabel admin
│   │   │   └── ie10-viewport-bug-workaround.js         # skrip kompatibilitas lama Bootstrap
│   │   └── psd/
│   │       └── mvp_landing_logo.psd                    # source desain logo
│   └── uploads/
│       └── products/                                   # hasil upload gambar produk dari admin
├── resources/                                          # source asset dan view
│   ├── css/
│   │   └── app.css                                     # entry Tailwind/Vite; belum jadi jalur styling utama UI aktif
│   ├── js/
│   │   ├── app.js                                      # entry JS Vite; hanya import bootstrap.js
│   │   └── bootstrap.js                                # set `axios` global + header `X-Requested-With`
│   └── views/
│       ├── home.blade.php                              # landing page Spare Soko
│       ├── welcome.blade.php                           # template Laravel bawaan; tidak dipakai route aktif
│       ├── layouts/
│       │   └── app.blade.php                           # layout publik utama
│       ├── auth/
│       │   ├── login.blade.php                         # form login
│       │   └── register.blade.php                      # form registrasi
│       ├── account/
│       │   ├── admin_settings.blade.php                # halaman settings khusus admin/staff
│       │   ├── settings.blade.php                      # account center customer
│       │   └── partials/
│       │       ├── address_modal.blade.php             # modal tambah/edit alamat
│       ├── admin/
│       │   ├── base.blade.php                          # layout admin
│       │   ├── dashboard.blade.php                     # dashboard KPI + revenue + recent activity
│       │   └── entity_list.blade.php                   # shell halaman entity management
│       ├── carts/
│       │   ├── checkout_view.blade.php                 # halaman checkout guest/auth
│       │   ├── empty_cart.blade.php                    # empty state cart
│       │   └── view.blade.php                          # halaman cart
│       ├── orders/
│       │   ├── address_form.blade.php                  # form alamat saat checkout
│       │   ├── address_select.blade.php                # pilih alamat shipping saat checkout
│       │   ├── order_detail.blade.php                  # detail order + invoice modal
│       │   ├── order_list.blade.php                    # daftar order customer
│       │   ├── order_summary_short.blade.php           # ringkasan order reusable
│       │   └── partials/
│       │       └── invoice_modal.blade.php             # modal invoice order
│       ├── partials/
│       │   ├── footer.blade.php                        # footer landing page
│       │   ├── navbar.blade.php                        # navbar publik + auth/cart badge
│       │   ├── public_sidebar.blade.php                # sidebar customer saat login
│       │   ├── public/
│       │   │   └── account_modal.blade.php             # modal akses akun
│       │   └── admin/
│       │       ├── entity_table.blade.php              # tabel entity generik
│       │       ├── entity_table_shell.blade.php        # summary + filter + wrapper tabel
│       │       ├── modal_delete.blade.php              # modal konfirmasi hapus
│       │       ├── modal_detail.blade.php              # modal detail entity
│       │       ├── modal_form.blade.php                # modal form generik categories/products/orders
│       │       ├── sidebar.blade.php                   # sidebar admin
│       │       └── topbar.blade.php                    # topbar admin
│       └── products/
│           ├── _card.blade.php                         # kartu produk katalog/home
│           ├── _related_card.blade.php                 # kartu produk terkait
│           ├── index.blade.php                         # katalog produk dengan filter
│           └── show.blade.php                          # detail produk + add to cart AJAX
├── routes/
│   ├── console.php                                     # command `db:sqlite-to-mysql` + command `inspire`
│   └── web.php                                         # semua route storefront, auth, account, order, dan admin
├── storage/                                            # runtime data Laravel
│   ├── app/
│   │   ├── private/                                    # storage lokal privat
│   │   └── public/                                     # storage publik untuk `storage:link`
│   ├── framework/
│   │   ├── cache/                                      # cache framework
│   │   ├── sessions/                                   # session file jika driver file dipakai
│   │   ├── testing/                                    # artefak testing
│   │   └── views/                                      # compiled Blade
│   └── logs/                                           # log aplikasi
├── tests/                                              # automated test suite
│   ├── TestCase.php                                    # base test case Laravel
│   ├── Feature/
│   │   ├── CatalogPagesTest.php                        # test halaman publik katalog dan auth
│   │   ├── ExampleTest.php                             # smoke test respons home
│   │   ├── MinimumErdSchemaTest.php                    # validasi skema minimum ERP dan sinkronisasi field mirror
│   │   └── StorefrontModulesTest.php                   # test cart, settings, admin, checkout, inventory
│   └── Unit/
│       └── ExampleTest.php                             # unit test contoh bawaan
├── vendor/                                             # seluruh dependency Composer terinstal
├── .editorconfig                                       # aturan indentasi/encoding lintas editor
├── .env                                                # konfigurasi lokal aktif; DB saat ini SQLite
├── .env.example                                        # template environment
├── .gitattributes                                      # atribut Git
├── .gitignore                                          # file/folder yang diabaikan Git
├── .phpunit.result.cache                               # cache hasil eksekusi PHPUnit
├── artisan                                             # entry CLI Laravel
├── composer.json                                       # manifest PHP dependency dan script kerja utama
├── composer.lock                                       # lock versi dependency Composer
├── package.json                                        # manifest dependency frontend/dev tooling
├── phpunit.xml                                         # konfigurasi PHPUnit; test memakai SQLite in-memory
├── README.md                                           # petunjuk kerja repo ini
└── vite.config.js                                      # konfigurasi Vite + Laravel plugin
```

## 7. Alur Kerja Aplikasi

### Storefront customer

1. User membuka `/`.
2. `HomeController` mengambil featured product dan tiga produk aktif acak.
3. User membuka `/products`.
4. `ProductController@index` menerapkan filter dan paginasi.
5. User membuka `/products/{product}`.
6. `ProductController@show` memuat detail produk, galeri, spesifikasi, dan related products.
7. User klik `Add to Cart`.
8. Halaman detail memanggil `GET /cart?item={variation_id}&qty={qty}` via AJAX.
9. `CartController` membuat atau memperbarui `cart_items`.
10. Model `CartItem` menghitung `line_item_total`.
11. Hook model `Cart` memperbarui `subtotal`, `tax_total`, dan `total`.
12. User membuka `/cart`.
13. User dapat mencentang item tertentu untuk checkout.
14. `POST /cart/selection` menyimpan flag `is_selected`.
15. User lanjut ke `/checkout`.
16. Jika belum login, user dapat checkout sebagai guest atau login.
17. Sistem mengambil `UserCheckout` dan alamat default yang aktif.
18. Sistem membuat atau melanjutkan `order` draft terkait `cart`.
19. Sistem membuat snapshot item ke `order_items`.
20. User memilih alamat pengiriman.
21. Sistem menyiapkan order dengan metode pembayaran `cod`.
22. Saat final checkout, sistem memvalidasi data dan memotong stok produk yang dibeli.
23. Item yang berhasil dibeli dihapus dari cart.
24. User diarahkan ke detail order di `/orders/{order}`.

### Admin admin/staff

1. Admin login melalui `/login`.
2. Setelah login, staff otomatis diarahkan ke `/admin`.
3. Admin melihat dashboard KPI, revenue, dan aktivitas terbaru.
4. Admin membuka entity list seperti `products`, `categories`, atau `orders`.
5. Modal create/edit/detail/delete dimuat via AJAX.
6. Form modal dikirim via AJAX dan diproses `AdminController`.
7. Tabel dan summary di-refresh tanpa reload penuh.

## 8. Database

### DBMS aktif

- default lokal aktif: `SQLite`
- jalur migrasi sudah disiapkan untuk `MySQL`

### ORM dan pattern data access

- ORM: `Eloquent`
- pattern: `Active Record`

### Relasi utama

```text
laravel/                                                # root aplikasi Laravel untuk storefront + admin Spare Soko
├── .git/                                               # metadata Git repository
├── app/                                                # kode PHP inti aplikasi
│   ├── Http/                                           # layer request/response web
│   │   ├── Controllers/                                # orkestrasi alur bisnis berbasis route
│   │   │   ├── Controller.php                          # base controller kosong bawaan Laravel
│   │   │   ├── HomeController.php                      # landing page; ambil featured product + 3 produk aktif acak
│   │   │   ├── ProductController.php                   # katalog, filter, dan detail produk + related products
│   │   │   ├── CartController.php                      # cart session/auth, add/update/remove item, AJAX count/selection
│   │   │   ├── CheckoutController.php                  # guest checkout, address flow, payment selection, finalisasi order
│   │   │   ├── OrderController.php                     # riwayat order dan detail order customer/guest session
│   │   │   ├── Account/
│   │   │   │   └── AccountSettingsController.php       # tab biodata, alamat, pembayaran, keamanan; beda flow admin vs customer
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php                  # login, register, logout, redirect next, redirect staff ke admin
│   │   │   └── Admin/
│   │   │       └── AdminController.php                 # dashboard admin dan CRUD generik entity categories/products/orders
│   │   └── Middleware/
│   │       └── RedirectStaffToAdmin.php                # paksa user staff keluar dari storefront menuju dashboard admin
│   ├── Models/                                         # model Eloquent + relasi + accessor + hook sinkronisasi
│   │   ├── AccountProfile.php                          # profil tambahan user: WhatsApp, HP, tanggal lahir, gender
│   │   ├── Brand.php                                   # master brand ERP-style; primary key string
│   │   ├── Cart.php                                    # cart dengan subtotal/tax/total dan accessor selected summary
│   │   ├── CartItem.php                                # item cart; hitung line total dan refresh total cart via model hook
│   │   ├── Category.php                                # kategori produk; mirror title <-> nama_kategori
│   │   ├── Order.php                                   # order header; snapshot total, payment label, status label, mirror ERP
│   │   ├── OrderItem.php                               # snapshot item order; menjaga histori transaksi stabil
│   │   ├── Product.php                                 # entitas produk utama; mirror kolom storefront dan ERP minimum
│   │   ├── ProductCompatibility.php                    # daftar kendaraan yang kompatibel dengan produk
│   │   ├── ProductImage.php                            # gambar produk + accessor URL fallback
│   │   ├── ProductSpecification.php                    # spesifikasi teknis produk dalam pasangan label-value
│   │   ├── User.php                                    # autentikasi user, role admin/customer, relasi profile/cart/payment
│   │   ├── UserAddress.php                             # alamat shipping/billing milik checkout profile
│   │   ├── UserCheckout.php                            # profil checkout yang menjembatani guest dan authenticated user
│   │   └── Variation.php                               # varian produk + harga efektif + inventory per varian
│   ├── Providers/
│   │   └── AppServiceProvider.php                      # register helper global dan share `sharedCartCount` ke semua view
│   └── Support/
│       └── helpers.php                                 # helper `rupiah`, `rupiah_catalog`, `avatar_initials`, `resolve_path_value`
├── bootstrap/
│   ├── app.php                                         # bootstrap Laravel 13; routing, alias middleware, health endpoint `/up`
│   └── cache/                                          # cache bootstrap/compiled framework
├── config/                                             # konfigurasi runtime Laravel
│   ├── app.php                                         # konfigurasi global framework, locale, timezone, provider
│   ├── auth.php                                        # guard `web`, provider Eloquent `User`, reset password config
│   ├── cache.php                                       # cache store; env aktif memakai database cache
│   ├── database.php                                    # koneksi SQLite/MySQL/MariaDB/PgSQL/SQL Server
│   ├── filesystems.php                                 # disk `local`, `public`, `s3`; upload admin saat ini tetap ke `public/`
│   ├── logging.php                                     # log channel default `stack -> single`
│   ├── mail.php                                        # konfigurasi mailer; env aktif memakai `log`
│   ├── queue.php                                       # queue database sebagai default
│   ├── services.php                                    # placeholder integrasi Postmark, Resend, SES, Slack
│   └── session.php                                     # session driver database, cookie, same-site, serialisasi JSON
├── database/                                           # skema, seed, dan audit SQL
│   ├── factories/
│   │   └── UserFactory.php                             # factory user untuk kebutuhan testing/seed Laravel standar
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php    # users, password_reset_tokens, sessions
│   │   ├── 0001_01_01_000001_create_cache_table.php    # cache dan cache_locks berbasis database
│   │   ├── 0001_01_01_000002_create_jobs_table.php     # jobs, job_batches, failed_jobs untuk queue database
│   │   ├── 2026_04_06_000003_create_categories_table.php # tabel kategori produk
│   │   ├── 2026_04_06_000004_create_products_table.php # tabel produk dasar storefront
│   │   ├── 2026_04_06_000005_create_variations_table.php # tabel varian harga/stok per produk
│   │   ├── 2026_04_06_000006_create_category_product_table.php # pivot many-to-many category-product
│   │   ├── 2026_04_06_000007_create_account_profiles_table.php # profil tambahan akun
│   │   ├── 2026_04_06_000008_create_carts_table.php    # cart header dengan tax config
│   │   ├── 2026_04_06_000009_create_cart_items_table.php # item cart dan line total
│   │   ├── 2026_04_06_000010_create_user_checkouts_table.php # profil checkout berbasis email/user
│   │   ├── 2026_04_06_000011_create_user_addresses_table.php # alamat checkout awal
│   │   ├── 2026_04_06_000012_create_orders_table.php   # header order awal
│   │   ├── 2026_04_06_000013_add_spare_part_fields_to_products_table.php # SKU, OEM, brand, garansi, rating, review_count
│   │   ├── 2026_04_06_000014_create_product_compatibilities_table.php # kompatibilitas kendaraan
│   │   ├── 2026_04_06_000015_create_product_specifications_table.php # spesifikasi teknis
│   │   ├── 2026_04_06_000016_backfill_spare_part_product_profiles.php # backfill profil awal spare part + specs + compatibility
│   │   ├── 2026_04_06_000017_create_product_images_table.php # tabel gambar produk
│   │   ├── 2026_04_06_000018_backfill_product_images.php # backfill galeri gambar produk awal
│   │   ├── 2026_04_07_000001_add_payment_method_to_orders_table.php # tambah mode pembayaran order
│   │   ├── 2026_04_07_100000_add_customer_fields_to_account_profiles_table.php # HP, birth_date, gender
│   │   ├── 2026_04_07_100100_add_customer_fields_to_user_addresses_table.php # label, recipient, phone, default flag
│   │   ├── 2026_04_07_110000_add_is_selected_to_cart_items_table.php # centang item cart untuk partial checkout
│   │   ├── 2026_04_07_110100_add_checkout_snapshot_totals_to_orders_table.php # subtotal/tax/items total snapshot
│   │   ├── 2026_04_07_110200_create_order_items_table.php # snapshot item order
│   │   ├── 2026_04_07_130500_align_minimum_erd_structure.php # sinkronisasi ke skema ERP minimum + backfill data
│   │   └── 2026_04_07_140000_remove_shipping_amounts_from_existing_orders.php # set ongkir lama menjadi 0
│   ├── seeders/
│   │   ├── DatabaseSeeder.php                          # memanggil seed katalog dan seed store awal
│   │   ├── CatalogSeeder.php                           # isi kategori, produk, varian, specs, gambar, dan compatibility awal
│   │   └── StoreSeeder.php                             # isi akun admin, akun customer, address, cart, dan order awal
│   └── sql/
│       └── mysql_primary_data_audit.sql                # query audit hasil copy data SQLite ke MySQL
├── public/                                             # document root web server
│   ├── favicon.ico                                     # favicon browser
│   ├── index.php                                       # front controller Laravel
│   ├── robots.txt                                      # aturan crawler
│   ├── theme/                                          # asset UI aktif yang benar-benar dipakai Blade
│   │   ├── css/
│   │   │   ├── bootstrap.min.css                       # CSS Bootstrap
│   │   │   ├── custom.css                              # design token, layout publik/admin, komponen kustom utama
│   │   │   └── navbar-static-top.css                   # styling navbar turunan Bootstrap
│   │   ├── img/
│   │   │   ├── marketing1.jpg                          # gambar fallback/global marketing
│   │   │   ├── mvp_landing_logo.png                    # logo landing/store
│   │   │   └── products/                               # gambar produk katalog
│   │   ├── js/
│   │   │   ├── bootstrap.min.js                        # JavaScript Bootstrap
│   │   │   ├── custom.js                               # helper global frontend lintas halaman
│   │   │   ├── layout-shell.js                         # interaksi layout publik/admin
│   │   │   ├── catalog-page.js                         # filter dan pagination katalog
│   │   │   ├── product-detail-page.js                  # interaksi halaman detail produk
│   │   │   ├── cart-page.js                            # interaksi halaman cart
│   │   │   ├── admin-form-widgets.js                   # widget form admin: multiselect, repeater, preview, currency
│   │   │   ├── admin-entity-page.js                    # modal AJAX dan refresh tabel admin
│   │   │   └── ie10-viewport-bug-workaround.js         # skrip kompatibilitas lama Bootstrap
│   │   └── psd/
│   │       └── mvp_landing_logo.psd                    # source desain logo
│   └── uploads/
│       └── products/                                   # hasil upload gambar produk dari admin
├── resources/                                          # source asset dan view
│   ├── css/
│   │   └── app.css                                     # entry Tailwind/Vite; belum jadi jalur styling utama UI aktif
│   ├── js/
│   │   ├── app.js                                      # entry JS Vite; hanya import bootstrap.js
│   │   └── bootstrap.js                                # set `axios` global + header `X-Requested-With`
│   └── views/
│       ├── home.blade.php                              # landing page Spare Soko
│       ├── welcome.blade.php                           # template Laravel bawaan; tidak dipakai route aktif
│       ├── layouts/
│       │   └── app.blade.php                           # layout publik utama
│       ├── auth/
│       │   ├── login.blade.php                         # form login
│       │   └── register.blade.php                      # form registrasi
│       ├── account/
│       │   ├── admin_settings.blade.php                # halaman settings khusus admin/staff
│       │   ├── settings.blade.php                      # account center customer
│       │   └── partials/
│       │       ├── address_modal.blade.php             # modal tambah/edit alamat
│       ├── admin/
│       │   ├── base.blade.php                          # layout admin
│       │   ├── dashboard.blade.php                     # dashboard KPI + revenue + recent activity
│       │   └── entity_list.blade.php                   # shell halaman entity management
│       ├── carts/
│       │   ├── checkout_view.blade.php                 # halaman checkout guest/auth
│       │   ├── empty_cart.blade.php                    # empty state cart
│       │   └── view.blade.php                          # halaman cart
│       ├── orders/
│       │   ├── address_form.blade.php                  # form alamat saat checkout
│       │   ├── address_select.blade.php                # pilih alamat shipping saat checkout
│       │   ├── order_detail.blade.php                  # detail order + invoice modal
│       │   ├── order_list.blade.php                    # daftar order customer
│       │   ├── order_summary_short.blade.php           # ringkasan order reusable
│       │   └── partials/
│       │       └── invoice_modal.blade.php             # modal invoice order
│       ├── partials/
│       │   ├── footer.blade.php                        # footer landing page
│       │   ├── navbar.blade.php                        # navbar publik + auth/cart badge
│       │   ├── public_sidebar.blade.php                # sidebar customer saat login
│       │   ├── public/
│       │   │   └── account_modal.blade.php             # modal akses akun
│       │   └── admin/
│       │       ├── entity_table.blade.php              # tabel entity generik
│       │       ├── entity_table_shell.blade.php        # summary + filter + wrapper tabel
│       │       ├── modal_delete.blade.php              # modal konfirmasi hapus
│       │       ├── modal_detail.blade.php              # modal detail entity
│       │       ├── modal_form.blade.php                # modal form generik categories/products/orders
│       │       ├── sidebar.blade.php                   # sidebar admin
│       │       └── topbar.blade.php                    # topbar admin
│       └── products/
│           ├── _card.blade.php                         # kartu produk katalog/home
│           ├── _related_card.blade.php                 # kartu produk terkait
│           ├── index.blade.php                         # katalog produk dengan filter
│           └── show.blade.php                          # detail produk + add to cart AJAX
├── routes/
│   ├── console.php                                     # command `db:sqlite-to-mysql` + command `inspire`
│   └── web.php                                         # semua route storefront, auth, account, order, dan admin
├── storage/                                            # runtime data Laravel
│   ├── app/
│   │   ├── private/                                    # storage lokal privat
│   │   └── public/                                     # storage publik untuk `storage:link`
│   ├── framework/
│   │   ├── cache/                                      # cache framework
│   │   ├── sessions/                                   # session file jika driver file dipakai
│   │   ├── testing/                                    # artefak testing
│   │   └── views/                                      # compiled Blade
│   └── logs/                                           # log aplikasi
├── tests/                                              # automated test suite
│   ├── TestCase.php                                    # base test case Laravel
│   ├── Feature/
│   │   ├── CatalogPagesTest.php                        # test halaman publik katalog dan auth
│   │   ├── ExampleTest.php                             # smoke test respons home
│   │   ├── MinimumErdSchemaTest.php                    # validasi skema minimum ERP dan sinkronisasi field mirror
│   │   └── StorefrontModulesTest.php                   # test cart, settings, admin, checkout, inventory
│   └── Unit/
│       └── ExampleTest.php                             # unit test contoh bawaan
├── vendor/                                             # seluruh dependency Composer terinstal
├── .editorconfig                                       # aturan indentasi/encoding lintas editor
├── .env                                                # konfigurasi lokal aktif; DB saat ini SQLite
├── .env.example                                        # template environment
├── .gitattributes                                      # atribut Git
├── .gitignore                                          # file/folder yang diabaikan Git
├── .phpunit.result.cache                               # cache hasil eksekusi PHPUnit
├── artisan                                             # entry CLI Laravel
├── composer.json                                       # manifest PHP dependency dan script kerja utama
├── composer.lock                                       # lock versi dependency Composer
├── package.json                                        # manifest dependency frontend/dev tooling
├── phpunit.xml                                         # konfigurasi PHPUnit; test memakai SQLite in-memory
├── README.md                                           # petunjuk kerja repo ini
└── vite.config.js                                      # konfigurasi Vite + Laravel plugin
```

### Tabel inti dan fungsinya

- `users`
  - akun login utama
- `account_profiles`
  - profil tambahan user
- `categories`
  - kategori produk
- `brand`
  - master brand untuk kompatibilitas skema ERP
- `products`
  - tabel produk utama dengan field storefront modern dan field mirror ERP
- `variations`
  - varian harga dan stok
- `category_product`
  - pivot kategori tambahan
- `product_compatibilities`
  - daftar kendaraan yang kompatibel
- `product_specifications`
  - spesifikasi teknis produk
- `product_images`
  - galeri gambar produk
- `carts`
  - cart header
- `cart_items`
  - item pada cart
- `user_checkouts`
  - profil checkout berbasis email/user
- `user_addresses`
  - alamat checkout
- `orders`
  - header transaksi
- `order_items`
  - snapshot item transaksi
- `sessions`
  - session database driver
- `cache`, `cache_locks`
  - cache database driver
- `jobs`, `job_batches`, `failed_jobs`
  - queue database

### Karakteristik data layer

- validasi form dilakukan di controller
- banyak sinkronisasi data dijalankan melalui hook model `saving`, `saved`, dan `deleted`
- tidak ada `soft delete`
- sebagian besar tabel bisnis memakai `timestamps`
- tersedia seeder data awal lengkap
- tersedia script audit hasil copy SQLite ke MySQL

### Seeder

- `CatalogSeeder`
  - kategori, produk, gambar, compatibility, specs, variations
- `StoreSeeder`
  - akun admin, akun customer, address, cart, dan order awal

### Audit dan migrasi data lintas DB

Project ini memiliki command artisan custom:

- `php artisan db:sqlite-to-mysql`

Tujuannya untuk menyalin data utama aplikasi dari SQLite ke MySQL. Hasil migrasi dapat diverifikasi memakai:

- `database/sql/mysql_primary_data_audit.sql`

## 9. API

Project ini tidak memiliki REST API publik terpisah dalam `routes/api.php`. Namun ada endpoint web yang berfungsi sebagai API internal/AJAX.

### Storefront

- `GET /`
  - render landing page
- `GET /products`
  - render katalog produk dengan filter query string
- `GET /products/{product}`
  - render detail produk

### Cart

- `GET /cart`
  - render cart
  - jika diberi query `item`, `qty`, atau `delete`, route ini juga melakukan mutasi cart
- `GET /cart/count`
  - JSON jumlah item cart aktif
- `POST /cart/selection`
  - JSON update status selected item cart
- `POST /cart/remove-selected`
  - hapus item yang dipilih; JSON untuk AJAX, redirect untuk non-AJAX

### Checkout

- `GET /checkout`
  - render halaman checkout
- `POST /checkout`
  - membuat `UserCheckout` guest dari email
- `GET /checkout/address`
  - render halaman pemilihan alamat
- `POST /checkout/address`
  - simpan alamat pengiriman yang dipilih ke order draft
- `GET /checkout/address/add`
  - render form tambah alamat saat checkout
- `POST /checkout/address/add`
  - simpan alamat baru
- `POST /checkout/final`
  - finalisasi order, validasi payment mode, snapshot order, potong stok, dan bersihkan item cart terpilih

### Auth

- `GET /login`
  - render form login
- `POST /login`
  - autentikasi session user
- `GET /register`
  - render form registrasi
- `POST /register`
  - buat user baru, profile checkout, lalu login
- `POST /logout`
  - logout session

### Account

- `GET /settings`
  - render account settings
- `POST /settings`
  - update profil, password, atau alamat berdasarkan field `action`

### Orders

- `GET /orders`
  - render daftar order user login
- `GET /orders/{order}`
  - render detail order jika session/auth berhak mengakses

### Admin

- `GET /admin`
  - render dashboard admin
- `GET /admin/{entity}`
  - render entity list admin
  - dapat mengembalikan HTML partial jika AJAX
- `GET /admin/{entity}/{mode}`
  - render modal create
- `POST /admin/{entity}/{mode}`
  - simpan create entity
- `GET /admin/{entity}/{pk}/{mode}`
  - render modal detail/edit/delete
- `POST /admin/{entity}/{pk}/{mode}`
  - update atau delete entity

### Health endpoint

- `GET /up`
  - endpoint health check bawaan bootstrap Laravel 13

## Menjalankan Aplikasi

### Menjalankan server

```powershell
cd C:\Users\User\33\Spareprod\laravel
C:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe artisan serve
```

### Menjalankan test

```powershell
cd C:\Users\User\33\Spareprod\laravel
C:\laragon\bin\php\php-8.5.4-Win32-vs17-x64\php.exe artisan test
```

### Menjalankan workflow development lengkap

Jika environment Node dan Composer sudah siap:

```powershell
composer dev
```

Script ini menjalankan:

- `php artisan serve`
- `php artisan queue:listen`
- `php artisan pail`
- `npm run dev`

## Environment Aktif yang Terdeteksi

Berdasarkan `.env` lokal saat analisis:

- `APP_ENV=local`
- `APP_URL=http://localhost`
- `DB_CONNECTION=sqlite`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `MAIL_MAILER=log`
- `LOG_CHANNEL=stack`

## Testing

Framework test:

- `PHPUnit`

Lokasi test:

- `tests/Feature`
- `tests/Unit`

Konfigurasi test:

- `phpunit.xml`
- database test memakai `sqlite :memory:`
- session driver test memakai `array`
- queue driver test memakai `sync`

Area yang sudah diuji:

- katalog dan halaman publik
- auth page
- account settings
- redirect staff ke admin
- CRUD kategori admin
- update produk admin
- inventory deduction saat checkout
- validasi minimum ERD schema

## Catatan Penting

- folder aktif pengembangan harian adalah `laravel/`
- arsip Django/Python lama sudah dipindahkan keluar dari folder ini
- UI aktif lebih banyak memakai asset statis `public/theme/*`
- Tailwind/Vite sudah tersedia, tetapi belum menjadi jalur frontend utama
- payment mode `prepaid` belum benar-benar aktif penuh karena `client_token` checkout saat ini mengembalikan `null`

