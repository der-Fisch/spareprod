<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected $appends = [
        'image_url',
    ];

    protected static function booted(): void
    {
        static::saved(function (ProductImage $image) {
            $image->product()->first()?->refreshErpSummaryColumns();
        });

        static::deleted(function (ProductImage $image) {
            $image->product()->first()?->refreshErpSummaryColumns();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && preg_match('/^https?:\/\//i', $this->image_path)) {
            return $this->image_path;
        }

        if ($this->image_path && file_exists(public_path($this->image_path))) {
            return asset($this->image_path);
        }

        return asset('theme/img/marketing1.jpg');
    }
}

