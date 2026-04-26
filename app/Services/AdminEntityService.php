<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCompatibility;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminEntityService
{
    public function persist(string $entity, array $data, mixed $object): void
    {
        DB::transaction(function () use ($entity, $data, $object) {
            match ($entity) {
                'categories' => $this->persistCategory($data, $object),
                'products' => $this->persistProduct($data, $object),
                'orders' => $this->persistOrder($data, $object),
                default => null,
            };
        });
    }

    protected function persistCategory(array $data, ?Category $category): void
    {
        $category ??= new Category();
        $slug = $this->generateUniqueCategorySlug(
            (string) $data['title'],
            $category->exists ? (int) $category->id : null,
        );

        $category->fill([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
        ]);
        $category->save();
    }

    protected function persistProduct(array $data, ?Product $product): void
    {
        $product ??= new Product();
        $categoryId = filled($data['category_id'] ?? null)
            ? (int) $data['category_id']
            : null;

        $product->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sku' => $data['sku'] ?? null,
            'oem_number' => $data['oem_number'] ?? null,
            'brand_name' => $data['brand_name'] ?? null,
            'brand_type' => $data['brand_type'] ?? null,
            'warranty_label' => $data['warranty_label'] ?? null,
            'price' => $data['price'],
            'stok' => $data['stok'],
            'default_category_id' => $categoryId,
            'active' => (bool) ($data['active'] ?? false),
        ]);
        $product->save();

        $product->categories()->sync($categoryId ? [$categoryId] : []);
        $this->syncProductCompatibilities($product, $data['compatibility_entries'] ?? []);
        $this->syncProductSpecifications($product, $data['specification_entries'] ?? []);
        $this->syncProductImages($product, $data['image_entries'] ?? []);
        $product->syncPrimaryVariation();
    }

    protected function persistOrder(array $data, Order $order): void
    {
        $order->fill([
            'status' => $data['status'],
        ]);
        $order->save();
    }

    protected function syncProductCompatibilities(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'vehicle_name' => trim((string) ($entry['vehicle_name'] ?? '')),
                    'year_start' => filled($entry['year_start'] ?? null) ? (int) $entry['year_start'] : null,
                    'year_end' => filled($entry['year_end'] ?? null) ? (int) $entry['year_end'] : null,
                ];
            })
            ->filter();

        ProductCompatibility::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $index => $entry) {
            if (! filled($entry['vehicle_name'])) {
                continue;
            }

            ProductCompatibility::query()->create([
                'product_id' => $product->id,
                'vehicle_name' => $entry['vehicle_name'],
                'year_start' => $entry['year_start'],
                'year_end' => $entry['year_end'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function syncProductSpecifications(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'label' => trim((string) ($entry['label'] ?? '')),
                    'value' => trim((string) ($entry['value'] ?? '')),
                ];
            })
            ->filter();

        ProductSpecification::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $index => $entry) {
            if (! filled($entry['label']) || ! filled($entry['value'])) {
                continue;
            }

            ProductSpecification::query()->create([
                'product_id' => $product->id,
                'label' => $entry['label'],
                'value' => $entry['value'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function syncProductImages(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($entry) {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'image_path' => trim((string) ($entry['image_path'] ?? '')),
                    'alt_text' => trim((string) ($entry['alt_text'] ?? '')),
                    'image_file' => $entry['image_file'] ?? null,
                ];
            })
            ->filter();

        ProductImage::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $index => $entry) {
            $imagePath = $entry['image_path'];

            if (($entry['image_file'] ?? null) instanceof UploadedFile) {
                $imagePath = $this->storeProductImageUpload($entry['image_file']);
            }

            if (! filled($imagePath)) {
                continue;
            }

            ProductImage::query()->create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'alt_text' => $entry['alt_text'] ?: $product->title,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function storeProductImageUpload(UploadedFile $file): string
    {
        $directory = public_path('uploads/products');
        File::ensureDirectoryExists($directory);

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $filename = Str::uuid()->toString() . '.' . strtolower($extension);
        $file->move($directory, $filename);

        return 'uploads/products/' . $filename;
    }

    protected function generateUniqueCategorySlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'kategori';
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Category::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
