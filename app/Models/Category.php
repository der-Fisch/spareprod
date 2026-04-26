<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'nama_kategori',
        'slug',
        'description',
        'active',
        'judul',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if ($category->isDirty('nama_kategori')) {
                $category->title = $category->nama_kategori;
            } elseif ($category->isDirty('title')) {
                $category->nama_kategori = $category->title;
            } elseif (! filled($category->nama_kategori) && filled($category->title)) {
                $category->nama_kategori = $category->title;
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function defaultProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'default_category_id');
    }

    public function __toString(): string
    {
        return $this->judul ?: $this->nama_kategori;
    }

    public function getJudulAttribute(): ?string
    {
        return $this->attributes['title'] ?? $this->attributes['nama_kategori'] ?? null;
    }

    public function setJudulAttribute(?string $value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['nama_kategori'] = $value;
    }

    public function getDeskripsiAttribute(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function setDeskripsiAttribute(?string $value): void
    {
        $this->attributes['description'] = $value;
    }
}

