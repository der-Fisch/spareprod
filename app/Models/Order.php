<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_pembelian',
        'status',
        'payment_method',
        'cart_id',
        'user_id',
        'kode_produk',
        'jumlah',
        'user_checkout_id',
        'billing_address_id',
        'shipping_address_id',
        'shipping_total_price',
        'items_subtotal',
        'items_tax_total',
        'items_total',
        'order_total',
        'total_bayar',
        'tanggal_transaksi',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'shipping_total_price' => 'decimal:2',
            'items_subtotal' => 'decimal:2',
            'items_tax_total' => 'decimal:2',
            'items_total' => 'decimal:2',
            'order_total' => 'decimal:2',
            'total_bayar' => 'decimal:2',
            'jumlah' => 'integer',
            'tanggal_transaksi' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            $itemsTotal = $order->items_total;

            if ($itemsTotal === null && $order->cart) {
                $itemsTotal = (float) $order->cart->total;
            }

            $order->shipping_total_price = 0;

            $resolvedTotal = round((float) ($itemsTotal ?? 0), 2);

            if ($order->isDirty('total_bayar') && $order->total_bayar !== null) {
                $resolvedTotal = round((float) $order->total_bayar, 2);
            } elseif ($order->isDirty('order_total') && $order->order_total !== null) {
                $resolvedTotal = round((float) $order->order_total, 2);
            }

            $order->order_total = $resolvedTotal;
            $order->total_bayar = $resolvedTotal;
            $order->tanggal_transaksi = $order->tanggal_transaksi ?: $order->created_at ?: now();

            if (filled($order->id_pembelian)) {
                $order->order_id = $order->id_pembelian;
            } elseif (filled($order->order_id)) {
                $order->id_pembelian = $order->order_id;
            }

            if (! $order->user_id && $order->user_checkout_id) {
                $order->user_id = UserCheckout::query()->whereKey($order->user_checkout_id)->value('user_id');
            }

            if ($order->jumlah === null) {
                $order->jumlah = $order->orderItems()->sum('quantity') ?: $order->cart?->cartItems()->sum('quantity') ?: 0;
            }

            if (! filled($order->kode_produk)) {
                $order->kode_produk = $order->resolvePrimaryProductCode();
            }
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserCheckout::class, 'user_checkout_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return 'COD';
    }

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

    public function isCod(): bool
    {
        return ($this->payment_method ?? 'cod') === 'cod';
    }

    public function presentedItems()
    {
        if ($this->relationLoaded('orderItems') && $this->orderItems->isNotEmpty()) {
            return $this->orderItems;
        }

        if ($this->orderItems()->exists()) {
            return $this->orderItems()->get();
        }

        if ($this->relationLoaded('cart') && $this->cart?->relationLoaded('cartItems')) {
            return $this->cart->cartItems;
        }

        return $this->cart?->cartItems()->get() ?? collect();
    }

    public function displayItemCount(): int
    {
        return $this->presentedItems()->count();
    }

    public function getDisplayItemCountAttribute(): int
    {
        return $this->displayItemCount();
    }

    public function displaySubtotal(): float
    {
        return (float) ($this->items_subtotal ?? $this->cart?->subtotal ?? 0);
    }

    public function displayTaxTotal(): float
    {
        return (float) ($this->items_tax_total ?? $this->cart?->tax_total ?? 0);
    }

    public function getTotalPajakAttribute(): float
    {
        return $this->displayTaxTotal();
    }

    public function displayItemsTotal(): float
    {
        return (float) ($this->items_total ?? $this->cart?->total ?? 0);
    }

    public function displayItemSummaries(): array
    {
        return $this->presentedItems()
            ->map(function ($item) {
                $title = $item->product_title
                    ?? $item->item?->product?->title
                    ?? 'Product';

                $quantity = (int) ($item->quantity ?? $item->pivot?->quantity ?? 0);

                return trim($title) !== ''
                    ? $title . ($quantity > 0 ? ' x' . $quantity : '')
                    : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function getDisplayItemSummariesAttribute(): array
    {
        return $this->displayItemSummaries();
    }

    public function __toString(): string
    {
        return $this->id_pembelian ?: $this->order_id ?: ('Order #' . $this->id);
    }

    protected function resolvePrimaryProductCode(): ?string
    {
        $variationId = $this->orderItems()->value('variation_id');

        if (! $variationId && $this->cart_id) {
            $variationId = $this->cart?->cartItems()->value('variation_id');
        }

        if (! $variationId) {
            return null;
        }

        return Variation::query()
            ->join('products', 'products.id', '=', 'variations.product_id')
            ->where('variations.id', $variationId)
            ->value('products.kode_produk');
    }
}

