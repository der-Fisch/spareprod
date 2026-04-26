<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'variation_id',
        'quantity',
        'line_item_total',
        'is_selected',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'line_item_total' => 'decimal:2',
            'is_selected' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CartItem $item) {
            $item->quantity = max((int) $item->quantity, 0);
            if ($item->quantity > 0) {
                $variation = $item->relationLoaded('item') ? $item->item : $item->item()->first();
                if ($variation) {
                    $product = $variation->relationLoaded('product') ? $variation->product : $variation->product()->first();
                    $unitPrice = (float) ($product?->harga ?? $product?->price ?? $variation->price_for_cart);
                    $item->line_item_total = round($item->quantity * $unitPrice, 2);
                }
            }
        });

        static::saved(function (CartItem $item) {
            $item->cart()->first()?->refreshTotals();
        });

        static::deleted(function (CartItem $item) {
            $item->cart()->first()?->refreshTotals();
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    public function getProductImageUrlAttribute(): string
    {
        $variation = $this->relationLoaded('item') ? $this->item : $this->item()->with('product.images')->first();

        return $variation?->product?->image_url ?: asset('theme/img/marketing1.jpg');
    }

    public function getAvailableStockAttribute(): int
    {
        $variation = $this->relationLoaded('item') ? $this->item : $this->item()->with('product')->first();
        $product = $variation?->relationLoaded('product') ? $variation->product : $variation?->product()->first();

        if ($product) {
            return (int) $product->total_inventory;
        }

        return (int) ($variation?->inventory ?? 0);
    }

    public function getStockIssueMessageAttribute(): ?string
    {
        $variation = $this->relationLoaded('item') ? $this->item : $this->item()->with('product')->first();
        $productTitle = $variation?->product?->judul ?: 'produk ini';
        $availableStock = $this->available_stock;

        if ($availableStock <= 0) {
            return 'Stok untuk produk "' . $productTitle . '" sedang habis. Silakan tunggu admin/staff melakukan restock.';
        }

        if ((int) $this->quantity > $availableStock) {
            return 'Jumlah untuk produk "' . $productTitle . '" melebihi stok tersedia. Saat ini hanya tersedia ' . $availableStock . ' unit. Silakan kurangi jumlah atau tunggu admin/staff melakukan restock.';
        }

        return null;
    }

    public function getHasStockIssueAttribute(): bool
    {
        return $this->stock_issue_message !== null;
    }
}

