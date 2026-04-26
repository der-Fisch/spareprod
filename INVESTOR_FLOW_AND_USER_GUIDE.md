# Panduan Flow User dan Penjelasan Teknis

Dokumen ini menjelaskan alur aplikasi `Spare Soko` dari sudut pandang user dan developer, dimulai dari user melakukan register sampai order selesai diproses.

Dokumen ini dibuat untuk dua tujuan:

- membantu user memahami langkah penggunaan aplikasi dengan jelas
- membantu tim non-teknis memahami langkah penggunaan aplikasi dengan bahasa yang jelas
- membantu developer memahami file dan fungsi yang menjalankan tiap langkah tersebut

## 1. Ringkasan Singkat Flow Aplikasi

Alur utama aplikasi saat ini adalah:

1. user membuat akun
2. user login
3. user melihat katalog produk
4. user membuka detail produk
5. user menambahkan produk ke cart
6. user memilih item di cart yang ingin dibeli
7. user masuk ke checkout
8. user menyiapkan identitas checkout dan alamat pengiriman
9. user membuat order
10. sistem mengurangi stok dan menyimpan snapshot order
11. admin memverifikasi order dan mengubah status menjadi `paid`
12. admin dapat melanjutkan status ke `shipped`, yang di tampilan dibaca sebagai `Delivered`

## 2. Catatan Penting tentang Status Order

Sebelum masuk ke detail, ada satu hal penting yang perlu dipahami.

Di codebase saat ini, status order yang dipakai adalah:

- `draft` = order sementara, belum final
- `created` = order berhasil dibuat user, menunggu proses admin
- `paid` = order sudah dikonfirmasi pembayarannya oleh admin
- `shipped` = order sudah dikirim, dan di UI ditampilkan sebagai `Delivered`
- `refunded` = order dibatalkan atau dikembalikan

Potongan kode status ini ada di model order:

```php
public function getStatusLabelAttribute(): string
{
    return match ($this->status) {
        'created' => 'Pending',
        'paid' => 'Paid',
        'shipped' => 'Delivered',
        'refunded' => 'Refunded',
        default => ucfirst((string) $this->status),
    };
}
```

Artinya:

- jika akhir flow berhenti di status `paid`, maka pembayaran atau konfirmasi order sudah tervalidasi
- jika akhir flow berarti barang benar-benar diterima user, maka secara logika aplikasi saat ini status yang paling tepat adalah `shipped`, karena label tampilannya adalah `Delivered`

## 3. Peta File dan Fungsi yang Mengatur Flow Ini

Berikut file inti yang mengatur flow user:

- `routes/web.php`
- `app/Http/Controllers/Auth/AuthController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/CartController.php`
- `app/Http/Controllers/CheckoutController.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/Admin/AdminController.php`
- `app/Services/CartService.php`
- `app/Services/CheckoutService.php`
- `app/Services/AdminEntityService.php`
- `app/Models/Order.php`
- `app/Models/Product.php`
- `app/Models/Cart.php`
- `app/Models/Variation.php`

## 4. Routing Utama yang Membentuk Alur

Flow ini dihubungkan oleh route berikut:

```php
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout/final', [CheckoutController::class, 'final'])->name('checkout.final');

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
```

Secara sederhana, route ini membentuk perjalanan user dari akun, katalog, cart, checkout, sampai order selesai dibuat.

## 5. Langkah 1: User Melakukan Register

### Tujuan bisnis

User membuat akun agar identitasnya dapat dipakai untuk:

- login
- menyimpan profil
- menghubungkan data checkout
- menyimpan riwayat order

### Fungsi yang berjalan

File:

- `routes/web.php`
- `app/Http/Controllers/Auth/AuthController.php`

Potongan kode utama:

```php
public function register(Request $request): RedirectResponse
{
    $payload = $request->validate([
        'username' => ['required', 'string', 'max:150', 'unique:users,username'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $user = User::query()->create([
        'username' => $payload['username'],
        'email' => $payload['email'],
        'password' => Hash::make($payload['password']),
        'date_joined' => now(),
    ]);

    AccountProfile::query()->firstOrCreate(['user_id' => $user->id]);
    UserCheckout::query()->firstOrCreate(
        ['email' => $user->email],
        ['user_id' => $user->id]
    );

    Auth::login($user);
    $request->session()->regenerate();

    return redirect()->to($this->resolveNext($request->input('next')))
        ->with('success', 'Your account has been created.');
}
```

### Penjelasan sederhana

Saat user register:

1. sistem memvalidasi `username`, `email`, dan `password`
2. password langsung di-hash, jadi tidak disimpan mentah
3. data utama user dibuat di tabel `users`
4. sistem otomatis membuat profil kosong di `account_profiles`
5. sistem otomatis membuat identitas checkout di `user_checkouts`
6. user langsung dianggap login
7. session baru dibuat agar aman

### Dampak ke database

Tabel yang terisi:

- `users`
- `account_profiles`
- `user_checkouts`

## 6. Langkah 2: User Login

### Tujuan bisnis

Login memastikan hanya user aktif yang bisa masuk dan melanjutkan transaksi.

### Fungsi yang berjalan

Potongan kode utama:

```php
public function login(Request $request): RedirectResponse
{
    $credentials = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt([
        'username' => $credentials['username'],
        'password' => $credentials['password'],
        'is_active' => true,
    ], $request->boolean('remember'))) {
        return back()
            ->withErrors(['username' => 'Username atau password tidak valid.'])
            ->withInput($request->except('password'));
    }

    $request->session()->regenerate();

    if ($request->user()?->is_staff) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->to($this->resolveNext($request->input('next')));
}
```

### Penjelasan sederhana

Saat login:

1. sistem mengecek kombinasi username dan password
2. sistem juga memastikan `is_active = true`
3. session user diregenerasi untuk keamanan
4. jika user adalah staff atau admin, diarahkan ke dashboard admin
5. jika user biasa, diarahkan ke halaman tujuan berikutnya

## 7. Langkah 3: User Melihat Katalog Produk

### Tujuan bisnis

Katalog adalah pintu masuk utama untuk menemukan produk yang relevan.

### Fungsi yang berjalan

File:

- `app/Http/Controllers/ProductController.php`
- `app/Models/Product.php`

Potongan kode utama:

```php
public function index(Request $request)
{
    $productQuery = Product::query()
        ->active()
        ->with(['defaultCategory', 'categories', 'compatibilities', 'specifications', 'images']);

    if ($keyword = trim((string) $request->string('q'))) {
        $productQuery->where(function ($query) use ($keyword) {
            $query
                ->where('title', 'like', '%' . $keyword . '%')
                ->orWhere('description', 'like', '%' . $keyword . '%');
        });
    }

    if ($idKategori = $request->integer('category_id')) {
        $productQuery->whereHas('categories', fn ($query) => $query->whereKey($idKategori));
    }

    $paginatedProducts = $productQuery
        ->latest('id')
        ->paginate(15)
        ->withQueryString();
```

### Penjelasan sederhana

Saat membuka katalog:

1. sistem hanya menampilkan produk yang `active`
2. sistem mengambil kategori, spesifikasi, kompatibilitas, dan gambar sekaligus
3. user bisa mencari berdasarkan kata kunci
4. user bisa memfilter berdasarkan kategori dan rentang harga
5. hasil ditampilkan dengan pagination

Ini penting dari sisi produk karena menunjukkan bahwa katalog bukan daftar statis, tetapi sudah mendukung eksplorasi produk yang cukup matang.

## 8. Langkah 4: User Membuka Detail Produk

### Tujuan bisnis

Halaman detail membantu user memutuskan apakah produk cocok untuk dibeli.

### Fungsi yang berjalan

Potongan kode utama:

```php
public function show(Product $product)
{
    $product->load(['defaultCategory', 'categories', 'compatibilities', 'specifications', 'images']);

    $relatedProducts = Product::query()
        ->active()
        ->with(['defaultCategory', 'compatibilities', 'specifications', 'images'])
        ->whereKeyNot($product->id)
        ->where(function ($query) use ($product) {
            $query->where('default_category_id', $product->default_category_id);

            if ($product->categories->isNotEmpty()) {
                $query->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $product->categories->pluck('id')));
            }
        })
        ->take(3)
        ->get();

    return view('products.show', [
        'product' => $product,
        'relatedProducts' => $relatedProducts,
        'cartItemId' => $product->primaryVariation()?->id,
    ]);
}
```

### Penjelasan sederhana

Di halaman detail:

1. sistem memuat seluruh informasi produk
2. sistem juga menampilkan produk terkait
3. produk dihubungkan ke `variation` utama untuk kebutuhan cart

Artinya, walaupun tampilan user terlihat sederhana, backend tetap memakai struktur yang siap untuk varian produk.

## 9. Langkah 5: User Menambahkan Produk ke Cart

### Tujuan bisnis

Cart adalah area penampung sebelum user benar-benar membuat order.

### Fungsi yang berjalan

File:

- `app/Http/Controllers/CartController.php`
- `app/Services/CartService.php`

Potongan controller:

```php
public function store(AddCartItemRequest $request): RedirectResponse|JsonResponse
{
    $response = $this->cartService->addItem(
        $request,
        (int) $request->validated('variation_id'),
        (int) $request->validated('quantity')
    );

    if ($this->isAjax($request)) {
        return response()->json($response);
    }

    return redirect()->route('cart.index')->with('success', $response['flash_message']);
}
```

Potongan service:

```php
public function addItem(Request $request, int $variationId, int $quantity): array
{
    $cart = $this->resolveCart($request);
    $variation = Variation::query()->with(['product.images'])->findOrFail($variationId);

    $cartItem = CartItem::query()->firstOrCreate(
        [
            'cart_id' => $cart->id,
            'variation_id' => $variation->id,
        ],
        [
            'is_selected' => true,
        ]
    );

    $cartItem->quantity = $quantity;
    $cartItem->is_selected = true;
    $cartItem->setRelation('item', $variation);
    $cartItem->save();
```

### Penjelasan sederhana

Saat user klik beli atau tambah ke cart:

1. sistem mencari cart aktif dari session
2. jika belum ada, sistem membuat cart baru
3. sistem mencari variation produk yang dipilih
4. sistem membuat atau memperbarui `cart_items`
5. item otomatis ditandai sebagai `selected`

### Catatan bisnis

Cart di aplikasi ini tidak hanya menyimpan item, tetapi juga menyimpan pilihan item mana yang benar-benar akan di-checkout.

## 10. Langkah 6: User Mengelola Cart

### Tujuan bisnis

User dapat:

- mengubah jumlah item
- menghapus item
- memilih item tertentu saja untuk checkout

### Fungsi yang berjalan

Potongan service:

```php
public function updateSelection(Request $request, array $cartItemIds, bool $selected): array
{
    $cart = $this->resolveCart($request);
    $cartItems = $cart->cartItems()->whereIn('id', $cartItemIds)->get();

    foreach ($cartItems as $cartItem) {
        $cartItem->is_selected = $selected;
        $cartItem->save();
    }

    return $this->selectionSummary($request, $cart);
}
```

### Penjelasan sederhana

Ini berarti user tidak harus checkout seluruh isi cart. User bisa memilih item tertentu saja.

Untuk tim produk dan developer, ini penting karena:

- pengalaman checkout lebih fleksibel
- user tidak dipaksa membeli semua item sekaligus

## 11. Langkah 7: Sistem Mengecek Stok Sebelum Checkout

### Tujuan bisnis

Sistem harus mencegah order yang melebihi stok.

### Fungsi yang berjalan

Potongan service:

```php
public function stockIssueSummary(Collection $cartItems): ?string
{
    $stockIssues = $this->itemsWithStockIssues($cartItems);

    if ($stockIssues->isEmpty()) {
        return null;
    }

    $primaryMessage = $stockIssues->first()->stock_issue_message;

    if ($stockIssues->count() === 1) {
        return $primaryMessage;
    }

    return $primaryMessage . ' Periksa juga item lain di cart Anda yang mungkin perlu menunggu restock.';
}
```

Dan di checkout:

```php
if ($stockIssueSummary = $this->cartService->stockIssueSummary($selectedItems)) {
    return redirect()->route('cart.index')->with('error', $stockIssueSummary);
}
```

### Penjelasan sederhana

Sistem mengizinkan item masuk cart, tetapi sebelum checkout:

1. stok dicek ulang
2. jika stok habis, user dikembalikan ke cart
3. user diberi pesan bahwa harus menunggu restock admin atau staff

Ini adalah mekanisme penting untuk menghindari over-selling.

## 12. Langkah 8: User Masuk ke Checkout

### Tujuan bisnis

Checkout mengubah niat beli menjadi data transaksi yang siap diproses.

### Fungsi yang berjalan

File:

- `app/Http/Controllers/CheckoutController.php`
- `app/Services/CheckoutService.php`

Potongan kode utama:

```php
public function show(Request $request): View|RedirectResponse
{
    $cart = $this->cartService->resolveCart($request);
    $cart->load('cartItems.item.product.images');

    if ($cart->items()->count() < 1) {
        return redirect()->route('cart.index');
    }

    $selectedItems = $this->cartService->selectedItems($cart);
    if ($selectedItems->isEmpty()) {
        return redirect()->route('cart.index')->with('info', 'Pilih minimal satu product di cart untuk lanjut checkout.');
    }

    $order = $this->checkoutService->resolveOrder($request, $cart);
    $order->payment_method = 'cod';
    $order->save();
    $this->checkoutService->syncOrderSnapshot($order, $cart, $selectedItems);
```

### Penjelasan sederhana

Saat user membuka checkout:

1. sistem memastikan cart tidak kosong
2. sistem memastikan ada item yang dipilih
3. sistem membuat atau mengambil order `draft`
4. metode pembayaran dipaksa ke `cod`
5. sistem membuat snapshot isi order

### Kenapa snapshot penting

Snapshot berarti sistem menyimpan kondisi item saat checkout, sehingga jika data produk berubah setelahnya, detail order tetap konsisten.

## 13. Langkah 9: Identitas Checkout User

### Tujuan bisnis

Sistem perlu tahu order ini milik siapa, bahkan jika alur checkout memakai sesi yang belum sepenuhnya lengkap.

### Fungsi yang berjalan

Potongan service:

```php
public function resolveCheckoutUser(Request $request, bool $storeAuthenticatedUser = true): ?UserCheckout
{
    if ($request->user()) {
        $checkoutUser = UserCheckout::query()->firstOrCreate(
            ['email' => $request->user()->email],
            ['user_id' => $request->user()->id]
        );

        if ($storeAuthenticatedUser) {
            $checkoutUser->user_id = $request->user()->id;
            $checkoutUser->save();
            $request->session()->put('user_checkout_id', $checkoutUser->id);
        }

        return $checkoutUser;
    }
```

### Penjelasan sederhana

Sistem memisahkan:

- `users` untuk akun login
- `user_checkouts` untuk identitas transaksi

Ini berguna jika nanti aplikasi ingin diperluas ke pola checkout yang lebih fleksibel.

## 14. Langkah 10: User Menambahkan atau Memilih Alamat Pengiriman

### Tujuan bisnis

Order tidak boleh final tanpa alamat pengiriman.

### Fungsi yang berjalan

Potongan kode penyimpanan alamat:

```php
public function storeAddress(StoreCheckoutAddressRequest $request): RedirectResponse
{
    $checkoutUser = $this->checkoutService->resolveCheckoutUser($request, false);

    $validatedData = $request->validated();

    $address = $checkoutUser->addresses()->create([
        'label' => $validatedData['label'] ?? 'Alamat',
        'nama_penerima' => $validatedData['nama_penerima'],
        'nomor_whatsapp' => $validatedData['nomor_whatsapp'],
        'tipe' => 'shipping',
        'nama_jalan' => $validatedData['nama_jalan'],
        'nama_kota' => $validatedData['nama_kota'],
        'negara' => $validatedData['negara'],
        'kode_pos' => $validatedData['kode_pos'],
        'is_default' => ! $checkoutUser->addresses()->exists() || ! empty($validatedData['is_default']),
    ]);
```

### Penjelasan sederhana

Di tahap ini user:

1. mengisi nama penerima
2. mengisi nomor WhatsApp
3. mengisi alamat lengkap
4. memilih alamat default jika perlu

Alamat ini kemudian dihubungkan ke order sebagai:

- `shipping_address_id`
- `billing_address_id`

## 15. Langkah 11: User Menyelesaikan Checkout dan Membuat Order

### Tujuan bisnis

Di titik ini, niat beli resmi berubah menjadi order.

### Fungsi yang berjalan

Potongan controller:

```php
public function final(FinalizeCheckoutRequest $request): RedirectResponse
{
    $cart = $this->cartService->resolveCart($request);
    $selectedItems = $this->cartService->selectedItems($cart);
    $order = $this->checkoutService->resolveOrder($request, $cart, false);

    if (! $order || $selectedItems->isEmpty()) {
        return redirect()->route('cart.index')->with('info', 'Pilih minimal satu product di cart untuk lanjut checkout.');
    }

    $this->checkoutService->syncOrderSnapshot($order, $cart, $selectedItems);
    $order->loadMissing(['orderItems', 'shippingAddress']);

    if (! $order->shipping_address_id) {
        return redirect()->route('checkout.address')->with('danger', 'Pilih alamat pengiriman terlebih dahulu.');
    }

    $this->checkoutService->finalizeOrder($order, $cart, $selectedItems);
```

Potongan service:

```php
public function finalizeOrder(Order $order, Cart $cart, Collection $selectedItems): void
{
    DB::transaction(function () use ($order, $cart, $selectedItems) {
        $lockedItems = CartItem::query()
            ->whereKey($selectedItems->modelKeys())
            ->with('item.product.images')
            ->get();

        $this->syncOrderSnapshot($order, $cart, $lockedItems);

        foreach ($lockedItems as $cartItem) {
            $variation = $cartItem->item()->with('product')->first();
            $product = $variation?->product_id
                ? Product::query()->lockForUpdate()->find($variation->product_id)
                : null;

            $remainingStock = (int) ($product->stok ?? 0) - (int) $cartItem->quantity;

            if ($remainingStock < 0) {
                throw new \RuntimeException('Stok tidak mencukupi.');
            }

            $product->stok = $remainingStock;
            $product->save();
        }

        if (! $order->order_id) {
            $order->order_id = 'SSK-' . strtoupper(Str::random(8));
        }

        $order->payment_method = 'cod';
        $order->status = 'created';
        $order->save();

        CartItem::query()->whereKey($lockedItems->modelKeys())->delete();
    });
}
```

### Penjelasan sederhana

Saat user menekan tombol buat pesanan:

1. sistem mengecek ulang apakah order masih valid
2. sistem mengecek apakah alamat sudah ada
3. sistem mengunci data item yang dipilih
4. sistem mengecek stok sekali lagi dengan transaksi database
5. sistem mengurangi stok produk
6. sistem membuat `order_id`
7. sistem menetapkan metode bayar `cod`
8. sistem mengubah status menjadi `created`
9. item yang sudah diorder dihapus dari cart

### Kenapa ini penting

Ini menunjukkan aplikasi sudah punya logika transaksi yang cukup aman:

- stok dicek ulang
- stok dikurangi saat order final
- cart dibersihkan hanya untuk item yang berhasil di-order

## 16. Langkah 12: User Melihat Detail Order

### Tujuan bisnis

User harus bisa melihat hasil order yang baru dibuat.

### Fungsi yang berjalan

Potongan kode:

```php
public function show(Request $request, Order $order): View
{
    $sessionCheckoutId = $request->session()->get('user_checkout_id');
    $authenticatedCheckoutId = UserCheckout::query()->where('user_id', $request->user()?->id)->value('id');

    abort_unless(
        $order->user_checkout_id && in_array($order->user_checkout_id, array_filter([$sessionCheckoutId, $authenticatedCheckoutId]), true),
        404
    );

    $order->load(['cart.cartItems.item.product', 'orderItems', 'billingAddress', 'shippingAddress', 'user', 'accountUser']);

    return view('orders.order_detail', ['order' => $order]);
}
```

### Penjelasan sederhana

Sistem tidak membiarkan sembarang user membuka order orang lain. Order hanya bisa dibuka jika:

- session checkout cocok, atau
- user login tersebut memang pemilik order

Ini adalah kontrol akses penting untuk keamanan transaksi.

## 17. Langkah 13: Admin Mengubah Status Order Menjadi Paid

### Tujuan bisnis

Karena metode pembayaran saat ini hanya `COD`, maka admin berperan untuk memperbarui status order setelah proses bisnis berjalan.

### Fungsi yang berjalan

File:

- `app/Http/Controllers/Admin/AdminController.php`
- `app/Services/AdminEntityService.php`

Konfigurasi form admin untuk order:

```php
'orders' => [
    'fields' => [
        [
            'name' => 'status',
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                'created' => 'Pending',
                'paid' => 'Paid',
                'shipped' => 'Delivered',
                'refunded' => 'Refunded'
            ],
        ],
    ],
    'rules' => fn (?Order $order) => [
        'status' => ['required', 'in:created,paid,shipped,refunded'],
    ],
],
```

Penyimpanan status:

```php
protected function simpanOrder(array $data, Order $order): void
{
    $order->fill([
        'status' => $data['status'],
    ]);
    $order->save();
}
```

### Penjelasan sederhana

Saat admin membuka menu `Orders`:

1. admin memilih order
2. admin membuka modal edit
3. admin mengubah status
4. status disimpan ke tabel `orders`

Jika admin memilih `paid`, maka order dianggap sudah dikonfirmasi pembayarannya.

## 18. Langkah 14: Kondisi Akhir Flow

### Jika akhir yang dimaksud adalah pembayaran terkonfirmasi

Maka flow berakhir di:

- status `paid`

### Jika akhir yang dimaksud adalah barang sudah diterima user

Maka di codebase saat ini status yang paling dekat adalah:

- status `shipped`
- label tampilan: `Delivered`

Artinya, secara bisnis:

- `paid` = pembayaran selesai atau valid
- `shipped` = order selesai dikirim dan ditampilkan sebagai sudah diterima

## 19. Panduan User Singkat dari Awal Sampai Akhir

Berikut panduan user yang bisa dipahami tanpa harus membaca teknis kode:

### A. Membuat akun

1. buka halaman register
2. isi username, email, password, dan konfirmasi password
3. klik daftar
4. sistem akan membuat akun dan langsung login

### B. Memilih produk

1. buka halaman katalog produk
2. gunakan pencarian atau filter kategori jika diperlukan
3. buka detail produk
4. pastikan produk sesuai kebutuhan

### C. Menambahkan ke cart

1. klik tombol beli atau tambah ke cart
2. tentukan jumlah produk
3. produk akan masuk ke cart

### D. Menyeleksi item yang akan dibeli

1. buka halaman cart
2. centang item yang ingin dibeli
3. jika perlu, ubah jumlah atau hapus item lain

### E. Checkout

1. klik lanjut checkout
2. pastikan item yang dipilih valid
3. tambahkan atau pilih alamat pengiriman
4. periksa ringkasan pesanan
5. klik buat pesanan

### F. Setelah order dibuat

1. order akan tercatat dengan status `Pending`
2. user bisa membuka halaman detail order
3. admin akan memproses order
4. setelah dikonfirmasi, status order berubah menjadi `Paid`
5. jika order selesai dikirim, status dapat berubah menjadi `Delivered`

## 20. Kesimpulan

Secara produk, aplikasi ini sudah memiliki fondasi alur transaksi yang jelas:

- akun user terhubung ke profil dan identitas checkout
- katalog produk sudah dapat difilter dan dicari
- cart mendukung seleksi item
- checkout memverifikasi item dan alamat
- order dibuat dengan snapshot data transaksi
- stok dikurangi secara aman saat checkout final
- admin dapat mengontrol status order

Secara teknis, alur ini tidak berjalan dengan logika yang tersebar acak, tetapi cukup rapi karena dipisahkan ke:

- `Controller` untuk menerima request
- `Service` untuk logika bisnis inti
- `Model` untuk aturan data dan relasi

Dengan kata lain, aplikasi ini tidak hanya menampilkan antarmuka, tetapi sudah memiliki alur transaksi yang nyata dengan:

- validasi
- kontrol akses
- kontrol stok
- pencatatan order
- manajemen status transaksi

Jika diperlukan, dokumen ini bisa dilanjutkan menjadi versi kedua yang lebih visual, misalnya dalam bentuk:

- flowchart bisnis
- sequence diagram
- diagram hubungan tabel database
