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
    public function simpan(string $entitas, array $data, mixed $objek): void
    {
        DB::transaction(function () use ($entitas, $data, $objek) {
            match ($entitas) {
                'categories' => $this->simpanKategori($data, $objek),
                'products' => $this->saveProduct($data, $objek),
                'orders' => $this->simpanOrder($data, $objek),
                default => null,
            };
        });
    }

    protected function simpanKategori(array $data, ?Category $kategori): void
    {
        $kategori ??= new Category();
        $slug = $this->buatSlugKategoriUnik(
            (string) $data['judul'],
            $kategori->exists ? (int) $kategori->id : null,
        );

        $kategori->fill([
            'title' => $data['judul'],
            'slug' => $slug,
            'description' => $data['deskripsi'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
        ]);
        $kategori->save();
    }

    protected function saveProduct(array $data, ?Product $product): void
    {
        $product ??= new Product();
        $categoryId = filled($data['category_id'] ?? null)
            ? (int) $data['category_id']
            : null;
        $sku = $data['sku'] ?? null;

        if (! filled($sku)) {
            $sku = 'PRD-' . strtoupper(Str::random(8));
        }

        $product->fill([
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'sku' => $sku,
            'oem_number' => $data['oem_number'] ?? null,
            'brand_name' => $data['brand_name'] ?? null,
            'brand_type' => $data['brand_type'] ?? null,
            'warranty_label' => $data['warranty_label'] ?? null,
            'harga' => $data['harga'],
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

    protected function simpanOrder(array $data, Order $order): void
    {
        $order->fill([
            'status' => $data['status'],
        ]);
        $order->save();
    }

    protected function syncProductCompatibilities(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($baris) {
                if (! is_array($baris)) {
                    return null;
                }

                return [
                    'vehicle_name' => trim((string) ($baris['vehicle_name'] ?? '')),
                    'year_start' => filled($baris['year_start'] ?? null) ? (int) $baris['year_start'] : null,
                    'year_end' => filled($baris['year_end'] ?? null) ? (int) $baris['year_end'] : null,
                ];
            })
            ->filter();

        ProductCompatibility::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $order => $row) {
            if (! filled($row['vehicle_name'])) {
                continue;
            }

            ProductCompatibility::query()->create([
                'product_id' => $product->id,
                'vehicle_name' => $row['vehicle_name'],
                'year_start' => $row['year_start'],
                'year_end' => $row['year_end'],
                'sort_order' => $order + 1,
            ]);
        }
    }

    protected function syncProductSpecifications(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($baris) {
                if (! is_array($baris)) {
                    return null;
                }

                return [
                    'label' => trim((string) ($baris['label'] ?? '')),
                    'value' => trim((string) ($baris['value'] ?? '')),
                ];
            })
            ->filter();

        ProductSpecification::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $order => $row) {
            if (! filled($row['label']) || ! filled($row['value'])) {
                continue;
            }

            ProductSpecification::query()->create([
                'product_id' => $product->id,
                'label' => $row['label'],
                'value' => $row['value'],
                'sort_order' => $order + 1,
            ]);
        }
    }

    protected function syncProductImages(Product $product, array $entries): void
    {
        $entries = collect($entries)
            ->map(function ($baris) {
                if (! is_array($baris)) {
                    return null;
                }

                return [
                    'image_path' => trim((string) ($baris['image_path'] ?? '')),
                    'alt_text' => trim((string) ($baris['alt_text'] ?? '')),
                    'image_file' => $baris['image_file'] ?? null,
                ];
            })
            ->filter();

        ProductImage::query()->where('product_id', $product->id)->delete();

        foreach ($entries as $order => $row) {
            $imagePath = $row['image_path'];

            if (($row['image_file'] ?? null) instanceof UploadedFile) {
                $imagePath = $this->storeProductImageUpload($row['image_file']);
            }

            if (! filled($imagePath)) {
                continue;
            }

            ProductImage::query()->create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'alt_text' => $row['alt_text'] ?: $product->judul,
                'sort_order' => $order + 1,
            ]);
        }
    }

    protected function storeProductImageUpload(UploadedFile $file): string
    {
        $direktori = public_path('uploads/produk');
        File::ensureDirectoryExists($direktori);

        $ekstensi = $file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg';
        $namaFile = Str::uuid()->toString() . '.' . strtolower($ekstensi);
        $file->move($direktori, $namaFile);

        return 'uploads/produk/' . $namaFile;
    }

    protected function buatSlugKategoriUnik(string $judul, ?int $abaikanId = null): string
    {
        $slugDasar = Str::slug($judul) ?: 'kategori';
        $slug = $slugDasar;
        $akhiran = 2;

        while (
            Category::query()
                ->when($abaikanId, fn ($query) => $query->whereKeyNot($abaikanId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $slugDasar . '-' . $akhiran;
            $akhiran++;
        }

        return $slug;
    }
}
