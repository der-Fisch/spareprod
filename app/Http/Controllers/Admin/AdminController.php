<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminEntityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class AdminController extends Controller
{
    public function __construct(
        protected AdminEntityService $layananEntitas,
    ) {
    }

    public function dashboard(Request $request): View
    {
        $this->ensureStaff($request);

        $revenueRows = Order::query()
            ->with(['user', 'accountUser'])
            ->whereIn('status', ['paid', 'shipped'])
            ->latest('updated_at')
            ->take(8)
            ->get()
            ->map(function (Order $order) {
                return [
                    'order_id' => $order->order_id ?: ('Order #' . $order->id),
                    'customer' => $order->user?->email ?: $order->accountUser?->email ?: '-',
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
                            'type' => 'Product',
                            'title' => $product->judul,
                            'meta' => $product->defaultCategory?->title ?? 'Tanpa kategori',
                            'detail' => 'Data produk baru ditambahkan ke katalog.',
                            'recorded_at' => $product->created_at,
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

        return view('admin.dashboard', [
            'page_title' => 'Dashboard',
            'cards' => [
                ['label' => 'Total Products', 'value' => Product::query()->count(), 'accent' => 'orange', 'icon' => 'fa-cubes', 'note' => 'Product aktif di katalog'],
                ['label' => 'Total Categories', 'value' => Category::query()->count(), 'accent' => 'amber', 'icon' => 'fa-tags', 'note' => 'Kategori yang tersedia'],
                ['label' => 'Total Orders', 'value' => Order::query()->count(), 'accent' => 'green', 'icon' => 'fa-shopping-cart', 'note' => 'Order yang tersimpan'],
            ],
            'recorded_revenue_total' => $recordedRevenueTotal,
            'revenue_rows' => $revenueRows,
            'recent_rows' => $recentRows,
            'quick_actions' => [
                ['label' => 'Tambah Product', 'url' => route('admin.entity.modal.create', ['entity' => 'products', 'mode' => 'create']), 'kind' => 'modal'],
                ['label' => 'Lihat Product', 'url' => route('admin.entity.list', ['entity' => 'products']), 'kind' => 'link'],
                ['label' => 'Kelola Orders', 'url' => route('admin.entity.list', ['entity' => 'orders']), 'kind' => 'link'],
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
            'page_description' => 'Kelola data ' . strtolower($config['label']) . ' lewat alur pencarian dan modal yang lebih ringkas.',
            'page_obj' => $page,
            'summary_items' => ($config['summary'])($this->entityQuery($entity)),
            'search_query' => (string) $request->query('q', ''),
        ];

        if ($this->isAjax($request)) {
            return response()->json([
                'html' => view('partials.admin.entity_table_shell', $context)->render(),
            ]);
        }

        return view('admin.entity_list', $context);
    }

    public function modal(Request $request, string $entity, string $pkOrMode, ?string $mode = null): JsonResponse
    {
        $this->ensureStaff($request);

        [$pk, $mode] = $this->resolveModalRouteArguments($pkOrMode, $mode);

        $config = $this->entityConfig($entity);
        $object = $pk ? $config['model']::query()->findOrFail($pk) : null;

        if ($mode === 'detail') {
            return response()->json([
                'html' => view('partials.admin.modal_detail', compact('entity', 'config', 'object'))->render(),
            ]);
        }

        if ($mode === 'delete') {
            return response()->json([
                'html' => view('partials.admin.modal_delete', compact('entity', 'config', 'object'))->render(),
            ]);
        }

        abort_if(! in_array($mode, ['create', 'edit'], true), 404);
        abort_if($mode === 'create' && ! $config['can_create'], 400);
        abort_if($mode === 'edit' && ! $config['can_update'], 400);

        return response()->json([
            'html' => view('partials.admin.modal_form', [
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
                'message' => $config['singular'] . ' berhasil dihapus.',
            ]);
        }

        $payload = $this->normalizeEntityPayload($entity, $request->all());
        $validator = Validator::make($payload, $config['rules']($object), $config['messages'] ?? []);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'html' => view('partials.admin.modal_form', [
                    'entity' => $entity,
                    'config' => $config,
                    'mode' => $mode,
                    'object' => $object,
                    'input' => $payload,
                    'errorsBag' => $validator->errors(),
                ])->render(),
            ], 400);
        }

        try {
            $this->layananEntitas->simpan($entity, $validator->validated(), $object);
        } catch (Throwable $exception) {
            Log::error('Admin entity save failed.', [
                'entity' => $entity,
                'mode' => $mode,
                'object_id' => $object?->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'html' => view('partials.admin.modal_form', [
                    'entity' => $entity,
                    'config' => $config,
                    'mode' => $mode,
                    'object' => $object,
                    'input' => $payload,
                    'errorsBag' => new MessageBag([
                        'general' => ['Data belum bisa disimpan. Periksa kembali field yang diisi lalu coba lagi.'],
                    ]),
                ])->render(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $config['singular'] . ' berhasil ' . ($mode === 'create' ? 'ditambahkan' : 'diperbarui') . '.',
        ]);
    }

    protected function entityConfig(string $entity): array
    {
        $configs = [
            'categories' => [
                'label' => 'Categories',
                'singular' => 'Kategori',
                'model' => Category::class,
                'can_create' => true,
                'can_update' => true,
                'columns' => [
                    ['label' => 'Kategori', 'key' => 'judul'],
                    ['label' => 'Status', 'key' => 'active', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'judul', 'label' => 'Nama Kategori', 'type' => 'text', 'placeholder' => 'Contoh: Cooling System'],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea', 'placeholder' => 'Jelaskan kategori ini secara singkat.'],
                    ['name' => 'active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ],
                'summary' => fn (Builder $query) => ['Total Categories' => (clone $query)->count(), 'Active' => (clone $query)->where('active', true)->count()],
                'rules' => fn (?Category $category) => [
                    'judul' => ['required', 'string', 'max:255'],
                    'deskripsi' => ['nullable', 'string'],
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
                    ['label' => 'Gambar', 'key' => 'image_url', 'type' => 'image'],
                    ['label' => 'Product', 'key' => 'judul'],
                    ['label' => 'Kategori', 'key' => 'defaultCategory.title'],
                    ['label' => 'Stock', 'key' => 'stock_display_label'],
                    ['label' => 'Harga', 'key' => 'harga', 'type' => 'currency_catalog'],
                    ['label' => 'Status', 'key' => 'active', 'type' => 'boolean'],
                ],
                'fields' => [
                    ['name' => 'judul', 'label' => 'Judul', 'type' => 'text', 'placeholder' => 'Contoh: Battery Terminal Clamp'],
                    ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea', 'placeholder' => 'Jelaskan fungsi singkat, keunggulan, dan penggunaan produk.'],
                    ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'placeholder' => 'Contoh: BTC-12V-009'],
                    ['name' => 'brand_name', 'label' => 'Merek Name', 'type' => 'text', 'placeholder' => 'Contoh: Bosch'],
                    ['name' => 'warranty_label', 'label' => 'Warranty Label', 'type' => 'text', 'placeholder' => 'Contoh: Garansi Resmi 1 Bulan'],
                    ['name' => 'harga', 'label' => 'Harga', 'type' => 'currency_catalog', 'placeholder' => 'Contoh: Rp145.000'],
                    ['name' => 'stok', 'label' => 'Stock', 'type' => 'number', 'placeholder' => 'Contoh: 24'],
                    ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => Category::query()->orderBy('title')->pluck('title', 'id')->all(), 'placeholder' => 'Pilih kategori produk'],
                    ['name' => 'compatibility_entries', 'label' => 'Compatibilities', 'type' => 'compatibility_repeater', 'help_text' => 'Tambahkan kendaraan yang didukung satu per satu.'],
                    ['name' => 'specification_entries', 'label' => 'Technical Specifications', 'type' => 'specification_repeater', 'help_text' => 'Tambahkan spesifikasi teknis per baris agar lebih mudah dibaca.'],
                    ['name' => 'image_entries', 'label' => 'Product Images', 'type' => 'image_repeater', 'help_text' => 'Tambahkan gambar satu per satu dan periksa preview-nya sebelum menyimpan.'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
                'detail_fields' => [
                    ['label' => 'SKU', 'key' => 'sku'],
                    ['label' => 'Merek', 'key' => 'brand_name'],
                    ['label' => 'Warranty', 'key' => 'warranty_label'],
                    ['label' => 'Stock', 'key' => 'stock_display_label'],
                    ['label' => 'Compatibilities', 'key' => 'compatibility_list', 'type' => 'list'],
                    ['label' => 'Technical Specifications', 'key' => 'technical_specs', 'type' => 'key_value'],
                    ['label' => 'Product Images', 'key' => 'images', 'type' => 'image_list'],
                ],
                'summary' => fn (Builder $query) => ['Total Products' => (clone $query)->count(), 'Active' => (clone $query)->where('active', true)->count()],
                'rules' => fn (?Product $product) => [
                    'judul' => ['required', 'string', 'max:255'],
                    'deskripsi' => ['nullable', 'string'],
                    'sku' => ['nullable', 'string', 'max:255'],
                    'brand_name' => ['nullable', 'string', 'max:255'],
                    'warranty_label' => ['nullable', 'string', 'max:255'],
                    'harga' => ['required', 'numeric', 'min:0'],
                    'stok' => ['required', 'integer', 'min:0'],
                    'category_id' => ['nullable', 'exists:categories,id'],
                    'compatibility_entries' => ['nullable', 'array'],
                    'compatibility_entries.*.vehicle_name' => ['nullable', 'string', 'max:255'],
                    'compatibility_entries.*.year_start' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                    'compatibility_entries.*.year_end' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                    'specification_entries' => ['nullable', 'array'],
                    'specification_entries.*.label' => ['nullable', 'string', 'max:255'],
                    'specification_entries.*.value' => ['nullable', 'string', 'max:255'],
                    'image_entries' => ['nullable', 'array'],
                    'image_entries.*.image_path' => ['nullable', 'string', 'max:255'],
                    'image_entries.*.alt_text' => ['nullable', 'string', 'max:255'],
                    'image_entries.*.image_file' => ['nullable', 'image', 'max:4096'],
                    'active' => ['nullable', 'boolean'],
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
        ];

        abort_unless(array_key_exists($entity, $configs), 404);

        return $configs[$entity];
    }

    protected function entityQuery(string $entity): Builder
    {
        return match ($entity) {
            'categories' => Category::query()->latest('created_at'),
            'products' => Product::query()->with(['defaultCategory', 'categories', 'compatibilities', 'specifications', 'images'])->latest('id'),
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
            'orders' => $query->where(fn ($q) => $q->where('order_id', 'like', '%' . $term . '%')->orWhere('status', 'like', '%' . $term . '%')->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', '%' . $term . '%'))),
            default => null,
        };
    }

    protected function entityValues(string $entity, mixed $object): array
    {
        return match ($entity) {
            'products' => [
                'judul' => $object->judul,
                'deskripsi' => $object->deskripsi,
                'sku' => $object->sku,
                'brand_name' => $object->brand_name,
                'warranty_label' => $object->warranty_label,
                'harga' => $object->harga,
                'stok' => $object->stok,
                'category_id' => $object->default_category_id ?: $object->categories()->pluck('categories.id')->first(),
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
            'orders' => [
                'status' => $object->status,
                'status_update_note' => 'Status order akan diperbarui tanpa mengubah detail transaksi lainnya.',
            ],
            default => $object->toArray(),
        };
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

    protected function normalizeEntityPayload(string $entity, array $payload): array
    {
        if ($entity === 'categories') {
            $payload['judul'] ??= $payload['title'] ?? null;
            $payload['deskripsi'] ??= $payload['description'] ?? null;
        }

        if ($entity === 'products') {
            $payload['judul'] ??= $payload['title'] ?? null;
            $payload['deskripsi'] ??= $payload['description'] ?? null;
            $payload['harga'] ??= $payload['price'] ?? null;
        }

        return $payload;
    }
}
