<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompatibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'vehicle_name',
        'year_start',
        'year_end',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'year_start' => 'integer',
            'year_end' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (ProductCompatibility $compatibility) {
            $compatibility->product()->first()?->refreshErpSummaryColumns();
        });

        static::deleted(function (ProductCompatibility $compatibility) {
            $compatibility->product()->first()?->refreshErpSummaryColumns();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLabelAttribute(): string
    {
        if ($this->year_start && $this->year_end) {
            return sprintf('%s (%d-%d)', $this->vehicle_name, $this->year_start, $this->year_end);
        }

        return $this->vehicle_name;
    }
}

