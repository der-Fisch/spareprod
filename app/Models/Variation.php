<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'price',
        'sale_price',
        'active',
        'inventory',
    ];

    protected $appends = [
        'effective_price',
        'formatted_price',
        'formatted_sale_price',
        'formatted_effective_price',
        'stock_status',
        'stock_badge_label',
        'stock_badge_class',
        'stock_display_label',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'inventory' => 'integer',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Variation $variation) {
            $variation->product()->first()?->refreshErpSummaryColumns();
        });

        static::deleted(function (Variation $variation) {
            $variation->product()->first()?->refreshErpSummaryColumns();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'variation_id');
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getPriceForCartAttribute(): float
    {
        return $this->effective_price;
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->formatCatalogRupiah($this->price);
    }

    public function getFormattedSalePriceAttribute(): ?string
    {
        if ($this->sale_price === null) {
            return null;
        }

        return $this->formatCatalogRupiah($this->sale_price);
    }

    public function getFormattedEffectivePriceAttribute(): string
    {
        return $this->formatCatalogRupiah($this->effective_price);
    }

    public function getStockStatusAttribute(): string
    {
        return match (true) {
            ($this->inventory ?? 0) <= 0 => 'out_of_stock',
            ($this->inventory ?? 0) <= 10 => 'low_stock',
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

        return sprintf('%s (%d unit)', $this->stock_badge_label, (int) ($this->inventory ?? 0));
    }

    protected function formatCatalogRupiah(float|string $value): string
    {
        return 'Rp' . number_format((float) $value * 10000, 0, ',', '.');
    }
}
