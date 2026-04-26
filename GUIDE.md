Panduan Flow User dan Penjelasan Teknis

Dokumen ini menjelaskan alur penggunaan aplikasi dari awal sampai order berstatus 'paid'. Penjelasannya mengikuti flow yang benar-benar dijalankan aplikasi, disertai potongan kode asli dari file yang digunakan.

1. Langkah 1 : User Melakukan Register

A. Tujuan :
User membuat akun agar bisa masuk ke aplikasi, menyimpan identitas dasar, dan langsung terhubung ke alur checkout.

B. File yang Digunakan :
- 'web.php'
- 'AuthController.php'

C. Potongan :
'web.php'
```php
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});
```

'AuthController.php'
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

D. Penjelasan Potongan Kode :
- Route '/register' menampilkan form dan route POST '/register' menyimpan data user baru.
- Variabel '$payload' berisi hasil validasi input 'username', 'email', dan 'password'.
- Variabel '$user' menyimpan hasil pembuatan akun di tabel 'users'.
- 'AccountProfile::query()->firstOrCreate' membuat profil dasar agar akun siap dipakai.
- 'UserCheckout::query()->firstOrCreate' membuat identitas checkout agar user langsung bisa masuk ke flow transaksi.
- 'Auth::login($user)' membuat user langsung login setelah register.
- '$request->session()->regenerate()' membuat session baru yang aman setelah autentikasi berhasil.

E. Catatan :
- Ini adalah langkah awal yang wajib dilakukan user sebelum melanjutkan ke pembelian.
- Hasil dari langkah ini adalah akun aktif, user login, dan data dasar transaksi sudah siap.

2. Langkah 2 : User Login

A. Tujuan :
User masuk ke aplikasi menggunakan akun yang sudah dibuat agar dapat mengakses cart, checkout, settings, dan order miliknya.

B. File yang Digunakan :
- 'web.php'
- 'AuthController.php'

C. Potongan :
'AuthController.php'
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

D. Penjelasan Potongan Kode :
- Variabel '$credentials' berisi hasil validasi 'username' dan 'password'.
- 'Auth::attempt' memeriksa apakah akun cocok dan dalam keadaan aktif melalui 'is_active'.
- '$request->boolean('remember')' mengambil opsi remember me dari form login jika ada.
- Jika login gagal, sistem mengembalikan user ke halaman sebelumnya dengan pesan error.
- Jika login berhasil, session diregenerasi.
- Jika akun bertipe staff, user diarahkan ke dashboard admin.
- Jika akun biasa, user diarahkan ke halaman lanjutan yang relevan.

E. Catatan :
- Login adalah pintu masuk utama untuk melanjutkan aktivitas yang membutuhkan kepemilikan data.
- Setelah login berhasil, user bisa mulai melihat produk dan menyusun pesanan.

3. Langkah 3 : User Melihat Katalog Produk

A. Tujuan :
User melihat daftar produk yang tersedia dan dapat mencari produk yang paling relevan.

B. File yang Digunakan :
- 'web.php'
- 'ProductController.php'

C. Potongan :
'ProductController.php'
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

    if ($minimumPrice = $this->parseCatalogPrice($request->input('min_price'))) {
        $productQuery->where('price', '>=', $minimumPrice);
    }

    if ($maximumPrice = $this->parseCatalogPrice($request->input('max_price'))) {
        $productQuery->where('price', '<=', $maximumPrice);
    }

    $paginatedProducts = $productQuery
        ->latest('id')
        ->paginate(15)
        ->withQueryString();

    return view('products.index', [
        'categories' => Category::query()->where('active', true)->orderBy('title')->get(),
        'products' => $paginatedProducts,
        'resultsCount' => $paginatedProducts->total(),
        'filters' => $request->only(['q', 'category_id', 'min_price', 'max_price']),
    ]);
}
```

D. Penjelasan Potongan Kode :
- Variabel '$productQuery' adalah query utama untuk mengambil produk aktif.
- 'with([...])' mengambil relasi kategori, kompatibilitas, spesifikasi, dan gambar sekaligus.
- Variabel '$keyword' mengambil kata pencarian dari parameter 'q'.
- Variabel '$idKategori' mengambil filter kategori dari parameter 'category_id'.
- Variabel '$minimumPrice' dan '$maximumPrice' mengambil filter harga minimum dan maksimum.
- Variabel '$paginatedProducts' berisi hasil query yang sudah diurutkan dan dipaginasi 15 item per halaman.
- Data yang dikirim ke view adalah kategori, daftar produk, jumlah hasil, dan filter yang sedang aktif.

E. Catatan :
- Pada tahap ini user sedang memilih produk yang ingin dibeli.
- Fungsi pencarian dan filter membuat user lebih cepat menemukan produk yang sesuai.

4. Langkah 4 : User Membuka Detail Produk

A. Tujuan :
User melihat informasi produk secara lengkap sebelum memutuskan untuk membeli.

B. File yang Digunakan :
- 'ProductController.php'

C. Potongan :
'ProductController.php'
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

D. Penjelasan Potongan Kode :
- Variabel '$product' adalah produk yang dibuka user berdasarkan parameter route.
- '$product->load([...])' melengkapi data produk dengan kategori, spesifikasi, kompatibilitas, dan gambar.
- Variabel '$relatedProducts' mengambil produk lain yang masih relevan dengan kategori produk saat ini.
- 'whereKeyNot($product->id)' memastikan produk utama tidak muncul lagi di daftar rekomendasi.
- 'cartItemId' mengambil 'id' variation utama produk untuk dipakai saat user menambahkan ke cart.

E. Catatan :
- Ini adalah tahap pertimbangan user sebelum klik beli.
- Di halaman ini user melihat detail inti produk sekaligus produk serupa.

5. Langkah 5 : User Menambahkan Produk ke Cart

A. Tujuan :
User memasukkan produk yang dipilih ke cart agar siap diproses ke checkout.

B. File yang Digunakan :
- 'CartController.php'
- 'CartService.php'

C. Potongan :
'CartController.php'
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

'CartService.php'
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

    $itemAdded = $cartItem->wasRecentlyCreated;

    $cartItem->quantity = $quantity;
    $cartItem->is_selected = true;
    $cartItem->setRelation('item', $variation);
    $cartItem->save();

    return $this->buildMutationResponse(
        request: $request,
        cart: $cart,
        flashMessage: $itemAdded
            ? 'Product berhasil ditambahkan ke cart.'
            : 'Jumlah product di cart berhasil diperbarui.',
        cartItem: $cartItem,
        itemAdded: $itemAdded
    );
}
```

D. Penjelasan Potongan Kode :
- Controller mengambil 'variation_id' dan 'quantity' dari request yang sudah tervalidasi.
- Variabel '$cart' diambil dari session user atau dibuat baru jika belum ada.
- Variabel '$variation' mengambil variasi produk yang benar-benar dibeli.
- Variabel '$cartItem' mencari item cart yang sama, lalu membuat item baru jika belum ada.
- Variabel '$itemAdded' menandai apakah ini item baru atau pembaruan item lama.
- 'quantity' dan 'is_selected' disimpan agar item langsung siap dipilih untuk checkout.
- 'buildMutationResponse' membentuk respons akhir seperti subtotal, total cart, dan pesan sukses.

E. Catatan :
- Pada tahap ini user belum membuat order, user baru menyusun isi cart.
- Cart berfungsi sebagai tempat menampung produk sebelum checkout.

6. Langkah 6 : User Mengelola Isi Cart

A. Tujuan :
User menentukan item mana yang benar-benar akan dibeli, mengubah jumlahnya, atau menghapus item yang tidak jadi dibeli.

B. File yang Digunakan :
- 'CartService.php'

C. Potongan :
'CartService.php'
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

'CartService.php'
```php
public function removeSelected(Request $request): array
{
    $cart = $this->resolveCart($request);
    $selectedItems = $cart->selectedCartItems()->get();

    foreach ($selectedItems as $cartItem) {
        $cartItem->delete();
    }

    return $this->selectionSummary($request, $cart) + [
        'flash_message' => 'Product terpilih berhasil dihapus dari cart.',
    ];
}
```

D. Penjelasan Potongan Kode :
- Variabel '$cartItemIds' berisi daftar item cart yang dipilih user.
- Variabel '$selected' menentukan apakah item ditandai aktif untuk checkout atau tidak.
- Variabel '$cartItems' mengambil item cart yang benar-benar milik cart saat ini.
- Setiap item diubah nilai 'is_selected'-nya agar perhitungan checkout hanya memakai item terpilih.
- Variabel '$selectedItems' pada potongan kedua berisi semua item yang sedang aktif dipilih.
- Item yang dipilih bisa langsung dihapus sekaligus jika user membatalkan pilihan.

E. Catatan :
- Ini adalah tahap kurasi isi cart.
- User bisa melanjutkan checkout hanya dengan item yang dipilih.

7. Langkah 7 : Sistem Memvalidasi Cart Sebelum Checkout

A. Tujuan :
Sistem memastikan cart layak diproses, ada item yang dipilih, dan stok masih tersedia.

B. File yang Digunakan :
- 'CheckoutController.php'
- 'CartService.php'

C. Potongan :
'CheckoutController.php'
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

    if ($stockIssueSummary = $this->cartService->stockIssueSummary($selectedItems)) {
        return redirect()->route('cart.index')->with('error', $stockIssueSummary);
    }
```

'CartService.php'
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

D. Penjelasan Potongan Kode :
- Variabel '$cart' adalah cart aktif milik user.
- Variabel '$selectedItems' berisi item yang benar-benar dipilih untuk dibeli.
- Jika cart kosong, sistem mengembalikan user ke halaman cart.
- Jika tidak ada item yang dipilih, sistem memberi pesan bahwa user harus memilih minimal satu item.
- Variabel '$stockIssueSummary' mengambil ringkasan masalah stok jika ada.
- Jika ada item yang stoknya habis atau jumlahnya melebihi stok, checkout dihentikan dan user diarahkan kembali ke cart.

E. Catatan :
- Ini adalah pintu validasi sebelum order dibuat.
- Fungsinya menjaga agar checkout hanya memproses item yang benar-benar tersedia.

8. Langkah 8 : User Menyiapkan Data Checkout dan Alamat Pengiriman

A. Tujuan :
User memastikan order memiliki identitas checkout dan alamat pengiriman yang lengkap.

B. File yang Digunakan :
- 'CheckoutController.php'
- 'CheckoutService.php'

C. Potongan :
'CheckoutController.php'
```php
public function storeAddress(StoreCheckoutAddressRequest $request): RedirectResponse
{
    $checkoutUser = $this->checkoutService->resolveCheckoutUser($request, false);
    if (! $checkoutUser) {
        return redirect()->route('checkout');
    }

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

    if ($address->is_default) {
        $checkoutUser->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
    }

    return redirect()->route('checkout.address')->with('success', 'Alamat pengiriman berhasil disimpan.');
}
```

'CheckoutService.php'
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

    $checkoutId = $request->session()->get('user_checkout_id');

    return $checkoutId ? UserCheckout::query()->find($checkoutId) : null;
}
```

D. Penjelasan Potongan Kode :
- Variabel '$checkoutUser' adalah identitas checkout yang dipakai order saat ini.
- Variabel '$validatedData' berisi data alamat yang sudah lolos validasi.
- Sistem menyimpan 'nama_penerima', 'nomor_whatsapp', 'nama_jalan', 'nama_kota', 'negara', dan 'kode_pos' ke alamat user.
- Field 'tipe' diset ke 'shipping' karena alamat ini dipakai untuk pengiriman.
- 'is_default' menentukan alamat utama yang akan diprioritaskan pada checkout berikutnya.
- Jika user sudah login, 'resolveCheckoutUser' memastikan data checkout milik akun tersebut aktif dan tersimpan di session.

E. Catatan :
- Ini adalah tahap pengisian data pengiriman.
- Order tidak bisa diselesaikan tanpa alamat yang valid.

9. Langkah 9 : User Menyelesaikan Checkout dan Membuat Order

A. Tujuan :
User mengubah item yang dipilih di cart menjadi order resmi yang tersimpan di sistem.

B. File yang Digunakan :
- 'CheckoutController.php'
- 'CheckoutService.php'

C. Potongan :
'CheckoutController.php'
```php
public function final(FinalizeCheckoutRequest $request): RedirectResponse
{
    $cart = $this->cartService->resolveCart($request);
    $selectedItems = $this->cartService->selectedItems($cart);
    $order = $this->checkoutService->resolveOrder($request, $cart, false);

    if (! $order || $selectedItems->isEmpty()) {
        return redirect()->route('cart.index')->with('info', 'Pilih minimal satu product di cart untuk lanjut checkout.');
    }

    if ($stockIssueSummary = $this->cartService->stockIssueSummary($selectedItems)) {
        return redirect()->route('cart.index')->with('error', $stockIssueSummary);
    }

    $this->checkoutService->syncOrderSnapshot($order, $cart, $selectedItems);
    $order->loadMissing(['orderItems', 'shippingAddress']);

    if (! $order->shipping_address_id) {
        return redirect()->route('checkout.address')->with('danger', 'Pilih alamat pengiriman terlebih dahulu.');
    }

    if (! in_array($order->status, ['paid', 'shipped', 'refunded'], true)) {
        try {
            $this->checkoutService->finalizeOrder($order, $cart, $selectedItems);
        } catch (\RuntimeException $exception) {
            return redirect()->route('checkout')->with('danger', $exception->getMessage());
        }
    }

    $cart->refresh()->load('cartItems');
    $request->session()->put('cart_item_count', $cart->items()->count());
    $request->session()->forget(['order_id']);

    return redirect()
        ->route('orders.show', $order)
        ->with('checkout_success', 'Pesanan berhasil dibuat dan item terpilih telah dipindahkan dari cart.');
}
```

'CheckoutService.php'
```php
public function finalizeOrder(Order $order, Cart $cart, Collection $selectedItems): void
{
    DB::transaction(function () use ($order, $cart, $selectedItems) {
        $lockedItems = CartItem::query()
            ->whereKey($selectedItems->modelKeys())
            ->with('item.product.images')
            ->get();

        if ($lockedItems->isEmpty()) {
            throw new \RuntimeException('Pilih minimal satu product di cart untuk lanjut checkout.');
        }

        $this->syncOrderSnapshot($order, $cart, $lockedItems);

        foreach ($lockedItems as $cartItem) {
            $variation = $cartItem->item()->with('product')->first();
            $product = $variation?->product_id
                ? Product::query()->lockForUpdate()->find($variation->product_id)
                : null;

            if (! $product) {
                continue;
            }

            $remainingStock = (int) ($product->stok ?? 0) - (int) $cartItem->quantity;

            if ($remainingStock < 0) {
                if ((int) ($product->stok ?? 0) <= 0) {
                    throw new \RuntimeException('Stok untuk product "' . $product->judul . '" sedang habis. Silakan tunggu admin/staff melakukan restock.');
                }

                throw new \RuntimeException('Jumlah untuk product "' . $product->judul . '" melebihi stok tersedia. Saat ini hanya tersedia ' . (int) ($product->stok ?? 0) . ' unit. Silakan kurangi jumlah atau tunggu admin/staff melakukan restock.');
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

D. Penjelasan Potongan Kode :
- Variabel '$cart' mengambil cart aktif user.
- Variabel '$selectedItems' mengambil item yang benar-benar dipilih untuk dibeli.
- Variabel '$order' mengambil draft order aktif dari session dan cart yang sama.
- Sistem memastikan item masih ada dan alamat pengiriman sudah dipilih.
- 'syncOrderSnapshot' menyimpan snapshot isi order agar data order tetap stabil.
- Variabel '$lockedItems' mengunci item yang akan diproses di dalam transaksi database.
- Variabel '$variation' dan '$product' mengambil produk sumber yang stoknya akan dikurangi.
- Variabel '$remainingStock' menghitung sisa stok setelah quantity item dikurangi.
- Jika stok tidak cukup, sistem menghentikan proses dan mengembalikan pesan yang jelas.
- Jika stok cukup, sistem mengurangi stok, menetapkan 'payment_method' menjadi 'cod', mengubah status order ke 'created', lalu menghapus item terpilih dari cart.

E. Catatan :
- Ini adalah langkah inti pembentukan order.
- Setelah langkah ini selesai, cart sudah berkurang dan user mempunyai order resmi dengan status awal 'created'.

10. Langkah 10 : User Melihat Detail Order

A. Tujuan :
User melihat hasil checkout yang baru dibuat dan memastikan data order tersimpan dengan benar.

B. File yang Digunakan :
- 'OrderController.php'

C. Potongan :
'OrderController.php'
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

D. Penjelasan Potongan Kode :
- Variabel '$sessionCheckoutId' mengambil identitas checkout dari session.
- Variabel '$authenticatedCheckoutId' mengambil identitas checkout yang terhubung ke akun login.
- 'abort_unless' memastikan hanya pemilik order yang bisa membuka detail order tersebut.
- '$order->load([...])' memuat seluruh data penting seperti item order, alamat, dan user terkait.
- Data order kemudian dikirim ke halaman 'orders.order_detail'.

E. Catatan :
- Ini adalah halaman konfirmasi hasil transaksi dari sisi user.
- User bisa mengecek isi order, alamat, dan status pesanan di sini.

11. Langkah 11 : Status Order Diubah Menjadi 'paid'

A. Tujuan :
Order yang sudah dibuat user diproses lebih lanjut sampai statusnya berubah menjadi 'paid'.

B. File yang Digunakan :
- 'AdminController.php'
- 'AdminEntityService.php'
- 'Order.php'

C. Potongan :
'AdminController.php'
```php
'orders' => [
    'label' => 'Orders',
    'singular' => 'Order',
    'model' => Order::class,
    'can_create' => false,
    'can_update' => true,
    'columns' => [
        ['label' => 'Order', 'key' => 'order_id'],
        ['label' => 'Customer', 'key' => 'user.email'],
        ['label' => 'Status', 'key' => 'status_label', 'type' => 'badge'],
        ['label' => 'Items', 'key' => 'display_item_count', 'type' => 'count'],
        ['label' => 'Total', 'key' => 'order_total', 'type' => 'currency_catalog'],
    ],
    'fields' => [
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['created' => 'Pending', 'paid' => 'Paid', 'shipped' => 'Delivered', 'refunded' => 'Refunded'], 'placeholder' => 'Pilih status order'],
        ['name' => 'status_update_note', 'label' => 'Konfirmasi', 'type' => 'static_text', 'help_text' => 'Perubahan pada order dibatasi ke update status agar data transaksi tetap konsisten.'],
    ],
    'detail_fields' => [
        ['label' => 'Order ID', 'key' => 'order_id'],
        ['label' => 'Customer', 'key' => 'user.email'],
        ['label' => 'Product Dibeli', 'key' => 'display_item_summaries', 'type' => 'list'],
        ['label' => 'Status', 'key' => 'status_label'],
        ['label' => 'Alamat Pengiriman', 'key' => 'shippingAddress.address'],
        ['label' => 'Total Pembayaran', 'key' => 'total_bayar', 'type' => 'currency_catalog'],
    ],
    'summary' => fn (Builder $query) => ['Total Orders' => (clone $query)->count(), 'Paid' => (clone $query)->where('status', 'paid')->count()],
    'rules' => fn (?Order $order) => [
        'status' => ['required', 'in:created,paid,shipped,refunded'],
    ],
],
```

'AdminEntityService.php'
```php
protected function simpanOrder(array $data, Order $order): void
{
    $order->fill([
        'status' => $data['status'],
    ]);
    $order->save();
}
```

'Order.php'
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

D. Penjelasan Potongan Kode :
- Konfigurasi 'orders' di 'AdminController.php' menentukan bahwa status order dapat diubah dari panel admin.
- Field 'status' menyediakan pilihan 'created', 'paid', 'shipped', dan 'refunded'.
- Rule validasi memastikan hanya status yang sah yang bisa disimpan.
- Pada 'AdminEntityService.php', variabel '$data' membawa nilai status baru yang dipilih.
- '$order->fill([...])' mengisi status baru ke objek order, lalu '$order->save()' menyimpannya ke database.
- Di 'Order.php', status yang tersimpan diterjemahkan ke label tampilan yang rapi.

E. Catatan :
- Pada tahap ini user tidak lagi membuat data baru, tetapi menunggu proses order diperbarui.
- Tujuan akhirnya adalah status order berubah menjadi 'paid' sebagai tanda order sudah diproses ke tahap tersebut.

X. Panduan User Singkat

1. User membuka halaman register dan membuat akun.
2. User login memakai username dan password yang sudah dibuat.
3. User membuka halaman produk untuk melihat katalog.
4. User memilih satu produk lalu membuka detailnya.
5. User menambahkan produk ke cart.
6. User membuka cart lalu memilih item yang ingin dibeli.
7. User masuk ke checkout.
8. User menambahkan atau memilih alamat pengiriman.
9. User menekan tombol buat pesanan.
10. Sistem membuat order dengan status awal 'created'.
11. User membuka halaman detail order untuk melihat hasil checkout.
12. Status order kemudian diperbarui sampai menjadi 'paid'.

X. Kesimpulan

Flow aplikasi berjalan berurutan dan konsisten dari register sampai order berstatus 'paid'. User memulai dari pembuatan akun, masuk ke katalog, memilih produk, menyusun cart, melanjutkan checkout, lalu membuat order yang langsung tercatat di sistem. Setelah itu order diproses lebih lanjut melalui perubahan status sampai mencapai 'paid'.

Secara fungsi, setiap langkah memiliki file dan tanggung jawab yang jelas. 'AuthController.php' menangani register dan login, 'ProductController.php' menangani katalog dan detail produk, 'CartController.php' serta 'CartService.php' menangani isi cart, 'CheckoutController.php' dan 'CheckoutService.php' menangani checkout dan pembentukan order, lalu 'AdminController.php' dan 'AdminEntityService.php' menangani perubahan status order. Ini membuat alur user mudah diikuti dan data transaksi tetap terkendali.
