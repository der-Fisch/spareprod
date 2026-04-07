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

    protected $appends = [
        'remove_url',
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
                    $item->line_item_total = round($item->quantity * (float) $variation->price_for_cart, 2);
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

    public function getRemoveUrlAttribute(): string
    {
        return route('cart', ['item' => $this->variation_id, 'qty' => 1, 'delete' => 'True']);
    }

    public function getProductImageUrlAttribute(): string
    {
        $variation = $this->relationLoaded('item') ? $this->item : $this->item()->with('product.images')->first();

        return $variation?->product?->image_url ?: asset('theme/img/marketing1.jpg');
    }
}
