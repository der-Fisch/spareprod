<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'variation_id',
        'product_title',
        'variation_title',
        'product_image_url',
        'quantity',
        'unit_price',
        'line_item_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_item_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (OrderItem $item) {
            $item->order()->first()?->save();
        });

        static::deleted(function (OrderItem $item) {
            $item->order()->first()?->save();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }
}
