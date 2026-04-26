<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_produk',
        'nama_produk',
        'tipe_kendaraan',
        'kategori_id',
        'harga',
        'stok',
        'gambar',
        'brand_id',
        'title',
        'description',
        'price',
        'sku',
        'oem_number',
        'brand_name',
        'brand_type',
        'warranty_label',
        'rating',
        'active',
        'default_category_id',
    ];

    protected $appends = [
        'image_url',
        'formatted_price',
        'compatibility_list',
        'compatibility_summary',
        'technical_specs',
        'total_inventory',
        'stock_status',
        'stock_badge_label',
        'stock_badge_class',
        'stock_display_label',
        'rating_value',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'price' => 'decimal:2',
            'harga' => 'decimal:2',
            'rating' => 'decimal:1',
            'stok' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->isDirty('nama_produk')) {
                $product->title = $product->nama_produk;
            } elseif ($product->isDirty('title')) {
                $product->nama_produk = $product->title;
            } elseif (! filled($product->nama_produk) && filled($product->title)) {
                $product->nama_produk = $product->title;
            }

            if ($product->isDirty('kode_produk')) {
                $product->sku = $product->kode_produk;
            } elseif ($product->isDirty('sku')) {
                $product->kode_produk = $product->sku;
            } elseif (! filled($product->kode_produk) && filled($product->sku)) {
                $product->kode_produk = $product->sku;
            }

            if ($product->isDirty('kategori_id')) {
                $product->default_category_id = $product->kategori_id;
            } elseif ($product->isDirty('default_category_id')) {
                $product->kategori_id = $product->default_category_id;
            } elseif (! filled($product->kategori_id) && filled($product->default_category_id)) {
                $product->kategori_id = $product->default_category_id;
            }

            if ($product->isDirty('brand_name') || (! filled($product->brand_id) && filled($product->brand_name))) {
                $brandId = Str::slug($product->brand_name, '_') ?: 'brand';
                Brand::query()->updateOrCreate(
                    ['id' => $brandId],
                    ['nama_brand' => $product->brand_name]
                );
                $product->brand_id = $brandId;
            } elseif ($product->isDirty('brand_id') || (filled($product->brand_id) && ! filled($product->brand_name))) {
                $product->brand_name = Brand::query()->whereKey($product->brand_id)->value('nama_brand');
            }

            if ($product->isDirty('harga')) {
                $product->price = $product->harga;
            } elseif ($product->isDirty('price')) {
                $product->harga = $product->price;
            } elseif ($product->harga === null && $product->price !== null) {
                $product->harga = $product->price;
            }

            if ($product->stok === null && $product->exists) {
                $product->stok = $product->variations()->sum('inventory');
            }

            if (! filled($product->tipe_kendaraan) && $product->exists) {
                $product->tipe_kendaraan = $product->compatibilities()->orderBy('sort_order')->value('vehicle_name') ?: 'Universal';
            }

            if (! filled($product->gambar) && $product->exists) {
                $product->gambar = $product->images()->orderBy('sort_order')->value('image_path');
            }

            if (! filled($product->kode_produk) && $product->exists) {
                $product->kode_produk = 'PRD-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
                $product->sku = $product->kode_produk;
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    public function compatibilities(): HasMany
    {
        return $this->hasMany(ProductCompatibility::class)->orderBy('sort_order')->orderBy('id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): string
    {
        $primaryImage = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();

        if ($primaryImage) {
            return $primaryImage->image_url;
        }

        if ($this->gambar && file_exists(public_path($this->gambar))) {
            return asset($this->gambar);
        }

        $relativePath = 'theme/img/products/' . Str::slug($this->title) . '.jpg';

        if (file_exists(public_path($relativePath))) {
            return asset($relativePath);
        }

        return asset('theme/img/marketing1.jpg');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp' . number_format((float) ($this->harga ?? $this->price) * 10000, 0, ',', '.');
    }

    public function getCompatibilityListAttribute(): array
    {
        $compatibilities = $this->relationLoaded('compatibilities')
            ? $this->compatibilities
            : $this->compatibilities()->get();

        return $compatibilities
            ->map(fn (ProductCompatibility $compatibility) => $compatibility->label)
            ->filter()
            ->values()
            ->all();
    }

    public function getCompatibilitySummaryAttribute(): string
    {
        return implode(', ', $this->compatibility_list);
    }

    public function getTechnicalSpecsAttribute(): array
    {
        $specifications = $this->relationLoaded('specifications')
            ? $this->specifications
            : $this->specifications()->get();

        $mapped = [];

        foreach ($specifications as $specification) {
            $mapped[$specification->label] = $specification->value;
        }

        return $mapped;
    }

    public function getTotalInventoryAttribute(): int
    {
        if ($this->stok !== null) {
            return (int) $this->stok;
        }

        if ($this->relationLoaded('variations')) {
            return (int) $this->variations->sum('inventory');
        }

        return (int) $this->variations()->sum('inventory');
    }

    public function getStockStatusAttribute(): string
    {
        return match (true) {
            $this->total_inventory <= 0 => 'out_of_stock',
            $this->total_inventory <= 10 => 'low_stock',
            default => 'in_stock',
        };
    }

    public function getStockBadgeLabelAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'Stok Habis',
            'low_stock' => 'Stok Menipis',
            default => 'Tersedia',
        };
    }

    public function getStockBadgeClassAttribute(): string
    {
        return match ($this->stock_status) {
            'out_of_stock' => 'product-chip-stock-out',
            'low_stock' => 'product-chip-stock-low',
            default => 'product-chip-stock-ok',
        };
    }

    public function getStockDisplayLabelAttribute(): string
    {
        if ($this->stock_status === 'out_of_stock') {
            return 'Stok Habis';
        }

        return sprintf('%s (%d unit)', $this->stock_badge_label, $this->total_inventory);
    }

    public function getRatingValueAttribute(): string
    {
        if ($this->rating === null) {
            return '';
        }

        return number_format((float) ($this->rating ?? 0), 1);
    }

    public function __toString(): string
    {
        return $this->nama_produk ?: $this->title;
    }

    public function refreshErpSummaryColumns(): void
    {
        $this->forceFill([
            'nama_produk' => $this->nama_produk ?: $this->title,
            'kode_produk' => $this->kode_produk ?: $this->sku ?: 'PRD-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'kategori_id' => $this->kategori_id ?: $this->default_category_id,
            'harga' => $this->harga ?? $this->price,
            'stok' => $this->stok ?? (int) $this->variations()->sum('inventory'),
            'gambar' => $this->images()->orderBy('sort_order')->value('image_path') ?: $this->gambar,
            'tipe_kendaraan' => $this->compatibilities()->orderBy('sort_order')->value('vehicle_name') ?: ($this->tipe_kendaraan ?: 'Universal'),
        ])->saveQuietly();
    }

    public function primaryVariation(): ?Variation
    {
        if ($this->relationLoaded('variations')) {
            return $this->variations->sortBy('id')->first();
        }

        return $this->variations()->orderBy('id')->first();
    }

    public function syncPrimaryVariation(): Variation
    {
        $variation = $this->primaryVariation() ?? new Variation([
            'product_id' => $this->id,
            'title' => 'Default',
        ]);

        $variation->product_id = $this->id;
        $variation->title = $variation->title ?: 'Default';
        $variation->price = (float) ($this->price ?? $this->harga ?? 0);
        $variation->sale_price = null;
        $variation->inventory = (int) ($this->stok ?? 0);
        $variation->active = true;
        $variation->save();

        return $variation;
    }
}
