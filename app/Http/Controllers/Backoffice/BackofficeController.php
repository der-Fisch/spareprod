<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCompatibility;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\User;
use App\Models\Variation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BackofficeController extends Controller
{
    public function dashboard(Request $request): View
    {
        $this->ensureStaff($request);

        $revenueRows = Order::query()
            ->with(['user', 'accountUser', 'userPaymentMethod'])
            ->whereIn('status', ['paid', 'shipped'])
            ->latest('updated_at')
            ->take(8)
            ->get()
            ->map(function (Order $order) {
                return [
                    'order_id' => $order->order_id ?: ('Order #' . $order->id),
                    'customer' => $order->user?->email ?: $order->accountUser?->email ?: '-',
                    'payment' => $order->payment_method_label,
                    'status' => $order->status_label,
                    'total' => (float) $order->order_total,
                    'recorded_at' => $order->updated_at ?: $order->created_at,
                ];
            })
            ->all();

        $recordedRevenueTotal = collect($revenueRows)->sum('total');

        $recentRows = collect()
            ->concat(
                Product::query()
                    ->with('defaultCategory')
                    ->latest('created_at')
                    ->take(4)
                    ->get()
                    ->map(function (Product $product) {
                        return [
                            'type' => 'Produk',
                            'title' => $product->title,
                            'meta' => $product->defaultCategory?->title ?? 'Tanpa kategori',
                            'detail' => 'Data produk baru ditambahkan ke katalog.',
                            'recorded_at' => $product->created_at,
                        ];
                    })
            )
            ->concat(
                User::query()
                    ->latest('date_joined')
                    ->take(4)
                    ->get()
                    ->map(function (User $user) {
                        return [
                            'type' => 'User',
                            'title' => $user->username,
                            'meta' => $user->email,
                            'detail' => 'Akun baru terdaftar di sistem.',
                            'recorded_at' => $user->date_joined,
                        ];
                    })
            )
            ->concat(
                Order::query()
                    ->with(['user', 'accountUser'])
                    ->latest('created_at')
                    ->take(4)
                    ->get()
                    ->map(function (Order $order) {
                        return [
                            'type' => 'Order',
                            'title' => $order->order_id ?: ('Order #' . $order->id),
                            'meta' => $order->user?->email ?: $order->accountUser?->email ?: '-',
                            'detail' => 'Order masuk dengan status ' . $order->status_label . '.',
                            'recorded_at' => $order->created_at,
                        ];
                    })
            )
            ->sortByDesc(fn (array $row) => $row['recorded_at']?->getTimestamp() ?? 0)
            ->take(10)
            ->values()
            ->all();

        return view('backoffice.dashboard', [
            'page_title' => 'Dashboard',
            'cards' => [
                ['label' => 'Total Products', 'value' => Product::query()->count(), 'accent' => 'orange', 'icon' => 'fa-cubes', 'note' => 'Produk aktif di katalog'],
                ['label' => 'Total Categories', 'value' => Category::query()->count(), 'accent' => 'amber', 'icon' => 'fa-tags', 'note' => 'Kategori yang tersedia'],
                ['label' => 'Total Users', 'value' => User::query()->count(), 'accent' => 'blue', 'icon' => 'fa-users', 'note' => 'Akun customer dan staff'],
                ['label' => 'Total Orders', 'value' => Order::query()->count(), 'accent' => 'green', 'icon' => 'fa-shopping-cart', 'note' => 'Order yang tersimpan'],
            ],
            'recorded_revenue_total' => $recordedRevenueTotal,
            'revenue_rows' => $revenueRows,
            'recent_rows' => $recentRows,
            'quick_actions' => [
                ['label' => 'Tambah Produk', 'url' => route('backoffice.entity.modal.create', ['entity' => 'products', 'mode' => 'create']), 'kind' => 'modal'],
                ['label' => 'Lihat Produk', 'url' => route('backoffice.entity.list', ['entity' => 'products']), 'kind' => 'link'],
                ['label' => 'Kelola Orders', 'url' => route('backoffice.entity.list', ['entity' => 'orders']), 'kind' => 'link'],
            ],
        ]);
    }

    public function index(Request $request, string $entity): View|JsonResponse
    {
        $this->ensureStaff($request);

        $config = $this->entityConfig($entity);
        $query = $this->entityQuery($entity);
        $this->applySearch($query, $entity, (string) $request->query('q', ''));
        $page = $query->paginate(8)->withQueryString();

        $context = [
            'entity' => $entity,
            'entityConfig' => $config,
            'page_title' => $config['label'],
            'page_description' => 'Manage ' . strtolower($config['label']) . ' with a searchable, modal-based workflow.',
            'page_obj' => $page,
            'summary_items' => ($config['summary'])($this->entityQuery($entity)),
            'search_query' => (string) $request->query('q', ''),
        ];

        if ($this->isAjax($request)) {
            return response()->json([
                'html' => view('partials.backoffice.entity_table_shell', $context)->render(),
            ]);
        }

        return view('backoffice.entity_list', $context);
    }

    public function modal(Request $request, string $entity, string $pkOrMode, ?string $mode = null): JsonResponse
    {
        $this->ensureStaff($request);

        [$pk, $mode] = $this->resolveModalRouteArguments($pkOrMode, $mode);

        $config = $this->entityConfig($entity);
        $object = $pk ? $config['model']::query()->findOrFail($pk) : null;

        if ($mode === 'detail') {
            return response()->json([
                'html' => view('partials.backoffice.modal_detail', compact('entity', 'config', 'object'))->render(),
            ]);
        }

        if ($mode === 'delete') {
            return response()->json([
                'html' => view('partials.backoffice.modal_delete', compact('entity', 'config', 'object'))->render(),
            ]);
        }

        abort_if(! in_array($mode, ['create', 'edit'], true), 404);
        abort_if($mode === 'create' && ! $config['can_create'], 400);
        abort_if($mode === 'edit' && ! $config['can_update'], 400);

        return response()->json([
            'html' => view('partials.backoffice.modal_form', [
                'entity' => $entity,
                'config' => $config,
                'mode' => $mode,
                'object' => $object,
                'input' => $object ? $this->entityValues($entity, $object) : [],
                'errorsBag' => collect(),
            ])->render(),
        ]);
    }

    public function save(Request $request, string $entity, string $pkOrMode, ?string $mode = null): JsonResponse
    {
        $this->ensureStaff($request);

        [$pk, $mode] = $this->resolveModalRouteArguments($pkOrMode, $mode);

        $config = $this->entityConfig($entity);
        $object = $pk ? $config['model']::query()->findOrFail($pk) : null;

        if ($mode === 'delete') {
            abort_if(! $object, 404);
            $object->delete();

            return response()->json([
                'success' => true,
                'message' => $config['singular'] . ' deleted successfully.',
            ]);
        }

        $validator = Validator::make($request->all(), $config['rules']($object), $config['messages'] ?? []);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'html' => view('partials.backoffice.modal_form', [
                    'entity' => $entity,
                    'config' => $config,
                    'mode' => $mode,
                    'object' => $object,
                    'input' => $request->all(),
                    'errorsBag' => $validator->errors(),
                ])->render(),
            ], 400);
        }

        $this->persistEntity($entity, $validator->validated(), $object);

        return response()->json([
            'success' => true,
            'message' => $config['singular'] . ' ' . ($mode === 'create' ? 'created' : 'updated') . ' successfully.',
        ]);
    }

    protected function entityConfig(string $entity): array
    {
        $configs = [
            'categories' => [
                'label' => 'Categories',
                'singular' => 'Category',
                'model' => Category::class,
                'can_create' => true,
                'can_update' => true,
                'columns' => [
                    ['label' => 'Category', 'key' => 'title'],
                    ['label' => 'Slug', 'key' => 'slug'],
                    ['label' => 'Status', 'key' => 'active', 'type' => 'boolean'],
                    ['label' => 'Created', 'key' => 'created_at', 'type' => 'date'],
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'placeholder' => 'Contoh: Cooling System'],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'placeholder' => 'Contoh: cooling-system'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Jelaskan kategori ini secara singkat.'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
                'summary' => fn (Builder $query) => ['Total Categories' => (clone $query)->count(), 'Active' => (clone $query)->where('active', true)->count()],
                'rules' => fn (?Category $category) => [
                    'title' => ['required', 'string', 'max:255'],
                    'slug' => ['required', 'string', 'max:255', 'unique:categories,slug' . ($category ? ',' . $category->id : '')],
                    'description' => ['nullable', 'string'],
                    'active' => ['nullable', 'boolean'],
                ],
            ],
            'products' => [
                'label' => 'Products',
                'singular' => 'Product',
                'model' => Product::class,
                'can_create' => true,
                'can_update' => true,
                'columns' => [
                    ['label' => 'Product', 'key' => 'title'],
                    ['label' => 'Category', 'key' => 'defaultCategory.title'],
                    ['label' => 'Stock', 'key' => 'stock_display_label'],
                    ['label' => 'Price', 'key' => 'price', 'type' => 'currency_catalog'],
                    ['label' => 'Status', 'key' => 'active', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'placeholder' => 'Contoh: Battery Terminal Clamp'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Jelaskan fungsi singkat, keunggulan, dan penggunaan produk.'],
                    ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'placeholder' => 'Contoh: BTC-12V-009'],
                    ['name' => 'oem_number', 'label' => 'OEM Number', 'type' => 'text', 'placeholder' => 'Contoh: 04465-BZ140'],
                    ['name' => 'brand_name', 'label' => 'Brand Name', 'type' => 'text', 'placeholder' => 'Contoh: Bosch'],
                    ['name' => 'brand_type', 'label' => 'Brand Type', 'type' => 'select', 'options' => ['OEM' => 'OEM', 'Aftermarket' => 'Aftermarket'], 'placeholder' => 'Pilih tipe brand'],
                    ['name' => 'warranty_label', 'label' => 'Warranty Label', 'type' => 'text', 'placeholder' => 'Contoh: Garansi Resmi 1 Bulan'],
                    ['name' => 'rating', 'label' => 'Rating', 'type' => 'rating'],
                    ['name' => 'price', 'label' => 'Price', 'type' => 'currency_catalog', 'placeholder' => 'Contoh: Rp145.000'],
                    ['name' => 'default_category_id', 'label' => 'Default Category', 'type' => 'select', 'options' => Category::query()->orderBy('title')->pluck('title', 'id')->all(), 'placeholder' => 'Pilih kategori utama'],
                    ['name' => 'categories', 'label' => 'Categories', 'type' => 'multiselect', 'options' => Category::query()->orderBy('title')->pluck('title', 'id')->all(), 'placeholder' => 'Cari dan pilih satu atau lebih kategori'],
                    ['name' => 'compatibility_entries', 'label' => 'Compatibilities', 'type' => 'compatibility_repeater', 'help_text' => 'Tambahkan kendaraan yang didukung satu per satu.'],
                    ['name' => 'specification_entries', 'label' => 'Technical Specifications', 'type' => 'specification_repeater', 'help_text' => 'Tambahkan spesifikasi teknis per baris agar lebih mudah dibaca.'],
                    ['name' => 'variation_entries', 'label' => 'Variations & Stock', 'type' => 'variation_repeater', 'help_text' => 'Setiap variasi menyimpan harga, harga promo, dan stoknya masing-masing.'],
                    ['name' => 'image_entries', 'label' => 'Product Images', 'type' => 'image_repeater', 'help_text' => 'Tambahkan gambar satu per satu dan periksa preview-nya sebelum menyimpan.'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
                'detail_fields' => [
                    ['label' => 'SKU', 'key' => 'sku'],
                    ['label' => 'OEM Number', 'key' => 'oem_number'],
                    ['label' => 'Brand', 'key' => 'brand_name'],
                    ['label' => 'Brand Type', 'key' => 'brand_type'],
                    ['label' => 'Warranty', 'key' => 'warranty_label'],
                    ['label' => 'Rating', 'key' => 'rating_value'],
                    ['label' => 'Stock', 'key' => 'stock_display_label'],
                    ['label' => 'Compatibilities', 'key' => 'compatibility_list', 'type' => 'list'],
                    ['label' => 'Technical Specifications', 'key' => 'technical_specs', 'type' => 'key_value'],
                    ['label' => 'Variations', 'key' => 'variations', 'type' => 'variation_list'],
                    ['label' => 'Product Images', 'key' => 'images', 'type' => 'image_list'],
                ],
                'summary' => fn (Builder $query) => ['Total Products' => (clone $query)->count(), 'Active' => (clone $query)->where('active', true)->count()],
                'rules' => fn (?Product $product) => [
                    'title' => ['required', 'string', 'max:255'],
                    'description' => ['nullable', 'string'],
                    'sku' => ['nullable', 'string', 'max:255'],
                    'oem_number' => ['nullable', 'string', 'max:255'],
                    'brand_name' => ['nullable', 'string', 'max:255'],
                    'brand_type' => ['nullable', 'in:OEM,Aftermarket'],
                    'warranty_label' => ['nullable', 'string', 'max:255'],
                    'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
                    'price' => ['required', 'numeric', 'min:0'],
                    'default_category_id' => ['nullable', 'exists:categories,id'],
                    'categories' => ['nullable', 'array'],
                    'categories.*' => ['exists:categories,id'],
                    'compatibility_entries' => ['nullable', 'array'],
                    'compatibility_entries.*.vehicle_name' => ['nullable', 'string', 'max:255'],
                    'compatibility_entries.*.year_start' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                    'compatibility_entries.*.year_end' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                    'specification_entries' => ['nullable', 'array'],
                    'specification_entries.*.label' => ['nullable', 'string', 'max:255'],
                    'specification_entries.*.value' => ['nullable', 'string', 'max:255'],
                    'variation_entries' => ['nullable', 'array'],
                    'variation_entries.*.title' => ['nullable', 'string', 'max:255'],
                    'variation_entries.*.price' => ['nullable', 'numeric', 'min:0'],
                    'variation_entries.*.sale_price' => ['nullable', 'numeric', 'min:0'],
                    'variation_entries.*.inventory' => ['nullable', 'integer', 'min:0'],
                    'image_entries' => ['nullable', 'array'],
                    'image_entries.*.image_path' => ['nullable', 'string', 'max:255'],
                    'image_entries.*.alt_text' => ['nullable', 'string', 'max:255'],
                    'image_entries.*.image_file' => ['nullable', 'image', 'max:4096'],
                    'active' => ['nullable', 'boolean'],
                ],
            ],
            'users' => [
                'label' => 'Users',
                'singular' => 'User',
                'model' => User::class,
                'can_create' => true,
                'can_update' => true,
                'columns' => [
                    ['label' => 'Username', 'key' => 'username'],
                    ['label' => 'Email', 'key' => 'email'],
                    ['label' => 'Role', 'key' => 'is_staff', 'type' => 'role'],
                    ['label' => 'Status', 'key' => 'is_active', 'type' => 'boolean'],
                    ['label' => 'Joined', 'key' => 'date_joined', 'type' => 'date'],
                ],
                'fields' => [
                    ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'placeholder' => 'Contoh: admin_ops'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Contoh: admin@spareprod.test'],
                    ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'placeholder' => 'Contoh: Budi'],
                    ['name' => 'last_name', 'label' => 'Last Name', 'type' => 'text', 'placeholder' => 'Contoh: Santoso'],
                    ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'create_only' => true, 'placeholder' => 'Minimal 8 karakter'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                    ['name' => 'is_staff', 'label' => 'Staff', 'type' => 'checkbox'],
                ],
                'summary' => fn (Builder $query) => ['Total Users' => (clone $query)->count(), 'Staff' => (clone $query)->where('is_staff', true)->count()],
                'rules' => fn (?User $user) => [
                    'username' => ['required', 'string', 'max:150', 'unique:users,username' . ($user ? ',' . $user->id : '')],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email' . ($user ? ',' . $user->id : '')],
                    'first_name' => ['nullable', 'string', 'max:150'],
                    'last_name' => ['nullable', 'string', 'max:150'],
                    'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
                    'is_active' => ['nullable', 'boolean'],
                    'is_staff' => ['nullable', 'boolean'],
                ],
            ],
            'orders' => [
                'label' => 'Orders',
                'singular' => 'Order',
                'model' => Order::class,
                'can_create' => false,
                'can_update' => true,
                'columns' => [
                    ['label' => 'Order', 'key' => 'order_id'],
                    ['label' => 'Customer', 'key' => 'user.email'],
                    ['label' => 'Pembayaran', 'key' => 'payment_method_label'],
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
                    ['label' => 'Produk Dibeli', 'key' => 'display_item_summaries', 'type' => 'list'],
                    ['label' => 'Metode Pembayaran', 'key' => 'payment_method_label'],
                    ['label' => 'Status', 'key' => 'status_label'],
                    ['label' => 'Alamat Pengiriman', 'key' => 'shippingAddress.address'],
                    ['label' => 'Total Pembayaran', 'key' => 'total_bayar', 'type' => 'currency_catalog'],
                ],
                'summary' => fn (Builder $query) => ['Total Orders' => (clone $query)->count(), 'Paid' => (clone $query)->where('status', 'paid')->count()],
                'rules' => fn (?Order $order) => [
                    'status' => ['required', 'in:created,paid,shipped,refunded'],
                ],
            ],
        ];

        abort_unless(array_key_exists($entity, $configs), 404);

        return $configs[$entity];
    }

    protected function entityQuery(string $entity): Builder
    {
        return match ($entity) {
            'categories' => Category::query()->latest('created_at'),
            'products' => Product::query()->with(['defaultCategory', 'categories', 'compatibilities', 'specifications', 'variations', 'images'])->latest('id'),
            'users' => User::query()->latest('date_joined'),
            'orders' => Order::query()->with(['user', 'cart.items', 'orderItems'])->latest('id'),
            default => abort(404),
        };
    }

    protected function applySearch(Builder $query, string $entity, string $term): void
    {
        if ($term === '') {
            return;
        }

        match ($entity) {
            'categories' => $query->where(fn ($q) => $q->where('title', 'like', '%' . $term . '%')->orWhere('slug', 'like', '%' . $term . '%')->orWhere('description', 'like', '%' . $term . '%')),
            'products' => $query->where(fn ($q) => $q->where('title', 'like', '%' . $term . '%')->orWhere('description', 'like', '%' . $term . '%')->orWhereHas('defaultCategory', fn ($categoryQuery) => $categoryQuery->where('title', 'like', '%' . $term . '%'))),
            'users' => $query->where(fn ($q) => $q->where('username', 'like', '%' . $term . '%')->orWhere('email', 'like', '%' . $term . '%')->orWhere('first_name', 'like', '%' . $term . '%')->orWhere('last_name', 'like', '%' . $term . '%')),
            'orders' => $query->where(fn ($q) => $q->where('order_id', 'like', '%' . $term . '%')->orWhere('status', 'like', '%' . $term . '%')->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', '%' . $term . '%'))),
            default => null,
        };
    }

    protected function entityValues(string $entity, mixed $object): array
    {
        return match ($entity) {
            'products' => [
                'title' => $object->title,
                'description' => $object->description,
                'sku' => $object->sku,
                'oem_number' => $object->oem_number,
                'brand_name' => $object->brand_name,
                'brand_type' => $object->brand_type,
                'warranty_label' => $object->warranty_label,
                'rating' => $object->rating,
                'price' => $object->price,
                'default_category_id' => $object->default_category_id,
                'categories' => $object->categories()->pluck('categories.id')->all(),
                'compatibility_entries' => $object->compatibilities
                    ->map(fn ($item) => [
                        'vehicle_name' => $item->vehicle_name,
                        'year_start' => $item->year_start,
                        'year_end' => $item->year_end,
                    ])
                    ->values()
                    ->all(),
                'specification_entries' => $object->specifications
                    ->map(fn ($item) => [
                        'label' => $item->label,
                        'value' => $item->value,
                    ])
                    ->values()
                    ->all(),
                'variation_entries' => $object->variations
                    ->map(fn ($item) => [
                        'title' => $item->title,
                        'price' => $item->price,
                        'sale_price' => $item->sale_price,
                        'inventory' => $item->inventory ?? 0,
                    ])
                    ->values()
                    ->all(),
                'image_entries' => $object->images
                    ->map(fn ($item) => [
                        'image_path' => $item->image_path,
                        'alt_text' => $item->alt_text,
                        'image_file' => null,
                    ])
                    ->values()
                    ->all(),
                'active' => $object->active,
            ],
            'users' => [
                'username' => $object->username,
                'email' => $object->email,
                'first_name' => $object->first_name,
                'last_name' => $object->last_name,
                'is_active' => $object->is_active,
                'is_staff' => $object->is_staff,
            ],
            'orders' => [
                'status' => $object->status,
                'status_update_note' => 'Status order akan diperbarui tanpa mengubah detail transaksi lainnya.',
            ],
            default => $object->toArray(),
        };
    }

    protected function persistEntity(string $entity, array $data, mixed $object): void
    {
        DB::transaction(function () use ($entity, $data, $object) {
            if ($entity === 'categories') {
                $category = $object ?? new Category();
                $category->fill([
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?? null,
                    'active' => (bool) ($data['active'] ?? false),
                ]);
                $category->save();
                return;
            }

            if ($entity === 'products') {
                $product = $object ?? new Product();
                $product->fill([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'sku' => $data['sku'] ?? null,
                    'oem_number' => $data['oem_number'] ?? null,
                    'brand_name' => $data['brand_name'] ?? null,
                    'brand_type' => $data['brand_type'] ?? null,
                    'warranty_label' => $data['warranty_label'] ?? null,
                    'rating' => $data['rating'] ?? null,
                    'price' => $data['price'],
                    'default_category_id' => $data['default_category_id'] ?? null,
                    'active' => (bool) ($data['active'] ?? false),
                ]);
                $product->save();
                $product->categories()->sync($data['categories'] ?? []);
                $this->syncProductCompatibilities($product, $data['compatibility_entries'] ?? []);
                $this->syncProductSpecifications($product, $data['specification_entries'] ?? []);
                $this->syncProductImages($product, $data['image_entries'] ?? []);
                $this->syncProductVariations($product, $data['variation_entries'] ?? []);
                return;
            }

            if ($entity === 'users') {
                $user = $object ?? new User();
                $user->fill([
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? false),
                    'is_staff' => (bool) ($data['is_staff'] ?? false),
                    'date_joined' => $user->date_joined ?? now(),
                ]);
                if (! empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }
                $user->save();
                return;
            }

            if ($entity === 'orders') {
                $object->fill([
                    'status' => $data['status'],
                ]);
                $object->save();
            }
        });
    }

    protected function ensureStaff(Request $request): void
    {
        abort_unless($request->user() && $request->user()->is_staff, 404);
    }

    protected function isAjax(Request $request): bool
    {
        return $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    protected function resolveModalRouteArguments(string $pkOrMode, ?string $mode): array
    {
        $modeOnlyRoutes = ['create'];

        if ($mode === null || $mode === $pkOrMode || in_array($pkOrMode, $modeOnlyRoutes, true)) {
            return [null, $pkOrMode];
        }

        return [(int) $pkOrMode, $mode];
    }

    protected function syncProductCompatibilities(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'vehicle_name' => trim((string) ($entry['vehicle_name'] ?? '')),
                    'year_start' => filled($entry['year_start'] ?? null) ? (int) $entry['year_start'] : null,
                    'year_end' => filled($entry['year_end'] ?? null) ? (int) $entry['year_end'] : null,
                ];
            })
            ->filter();

        ProductCompatibility::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $index => $entry) {
            if (! filled($entry['vehicle_name'])) {
                continue;
            }

            ProductCompatibility::query()->create([
                'product_id' => $product->id,
                'vehicle_name' => $entry['vehicle_name'],
                'year_start' => $entry['year_start'],
                'year_end' => $entry['year_end'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function syncProductSpecifications(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'label' => trim((string) ($entry['label'] ?? '')),
                    'value' => trim((string) ($entry['value'] ?? '')),
                ];
            })
            ->filter();

        ProductSpecification::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $index => $entry) {
            if (! filled($entry['label']) || ! filled($entry['value'])) {
                continue;
            }

            ProductSpecification::query()->create([
                'product_id' => $product->id,
                'label' => $entry['label'],
                'value' => $entry['value'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function syncProductImages(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'image_path' => trim((string) ($entry['image_path'] ?? '')),
                    'alt_text' => trim((string) ($entry['alt_text'] ?? '')),
                    'image_file' => $entry['image_file'] ?? null,
                ];
            })
            ->filter();

        ProductImage::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $index => $entry) {
            $imagePath = $entry['image_path'];

            if (($entry['image_file'] ?? null) instanceof UploadedFile) {
                $imagePath = $this->storeProductImageUpload($entry['image_file']);
            }

            if (! filled($imagePath)) {
                continue;
            }

            ProductImage::query()->create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'alt_text' => $entry['alt_text'] ?: $product->title,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function syncProductVariations(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'title' => trim((string) ($entry['title'] ?? '')),
                    'price' => $entry['price'] ?? null,
                    'sale_price' => $entry['sale_price'] ?? null,
                    'inventory' => $entry['inventory'] ?? null,
                ];
            })
            ->filter();

        if ($entries->isEmpty()) {
            Variation::query()->where('product_id', $product->id)->delete();
            return;
        }

        $activeTitles = [];

        foreach ($entries as $entry) {
            if (! filled($entry['title']) || ! is_numeric($entry['price'] ?? null)) {
                continue;
            }

            $activeTitles[] = $entry['title'];

            Variation::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'title' => $entry['title'],
                ],
                [
                    'price' => (float) $entry['price'],
                    'sale_price' => filled($entry['sale_price']) && is_numeric($entry['sale_price']) ? (float) $entry['sale_price'] : null,
                    'inventory' => is_numeric($entry['inventory'] ?? null) ? (int) $entry['inventory'] : 0,
                    'active' => true,
                ]
            );
        }

        Variation::query()
            ->where('product_id', $product->id)
            ->whereNotIn('title', $activeTitles)
            ->delete();
    }

    protected function storeProductImageUpload(UploadedFile $file): string
    {
        $directory = public_path('uploads/products');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $filename = Str::uuid()->toString() . '.' . strtolower($extension);

        $file->move($directory, $filename);

        return 'uploads/products/' . $filename;
    }
}
