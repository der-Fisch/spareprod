<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subtotal',
        'tax_percentage',
        'tax_total',
        'total',
        'persentasi_pajak',
        'total_pajak',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_percentage' => 'decimal:5',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function selectedCartItems(): HasMany
    {
        return $this->hasMany(CartItem::class)->where('is_selected', true);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Variation::class, 'cart_items', 'cart_id', 'variation_id')
            ->withPivot(['quantity', 'line_item_total'])
            ->withTimestamps();
    }

    public function refreshTotals(): void
    {
        $subtotal = (float) $this->cartItems()->sum('line_item_total');
        $taxTotal = round($subtotal * (float) $this->tax_percentage, 2);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => round($subtotal + $taxTotal, 2),
        ])->save();
    }

    public function getSelectedSubtotalAttribute(): float
    {
        if ($this->relationLoaded('cartItems')) {
            return round((float) $this->cartItems->where('is_selected', true)->sum('line_item_total'), 2);
        }

        return round((float) $this->selectedCartItems()->sum('line_item_total'), 2);
    }

    public function getSelectedTaxTotalAttribute(): float
    {
        return round($this->selected_subtotal * (float) $this->tax_percentage, 2);
    }

    public function getSelectedTotalAttribute(): float
    {
        return round($this->selected_subtotal + $this->selected_tax_total, 2);
    }

    public function getSelectedItemCountAttribute(): int
    {
        if ($this->relationLoaded('cartItems')) {
            return $this->cartItems->where('is_selected', true)->count();
        }

        return $this->selectedCartItems()->count();
    }

    public function getAllItemsSelectedAttribute(): bool
    {
        $totalItems = $this->relationLoaded('cartItems')
            ? $this->cartItems->count()
            : $this->cartItems()->count();

        return $totalItems > 0 && $this->selected_item_count === $totalItems;
    }

    public function getPersentasiPajakAttribute(): float
    {
        return (float) ($this->attributes['tax_percentage'] ?? 0);
    }

    public function setPersentasiPajakAttribute(float|int|string|null $value): void
    {
        $this->attributes['tax_percentage'] = $value;
    }

    public function getTotalPajakAttribute(): float
    {
        return (float) ($this->attributes['tax_total'] ?? 0);
    }

    public function setTotalPajakAttribute(float|int|string|null $value): void
    {
        $this->attributes['tax_total'] = $value;
    }

    public function getTotalPajakTerpilihAttribute(): float
    {
        return $this->selected_tax_total;
    }
}

