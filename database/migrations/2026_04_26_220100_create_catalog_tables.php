<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nama_brand');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk')->unique();
            $table->string('nama_produk');
            $table->string('tipe_kendaraan')->nullable();
            $table->foreignId('kategori_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('harga', 12, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->string('gambar')->nullable();
            $table->string('brand_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('sku')->nullable()->unique();
            $table->string('oem_number')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('brand_type')->nullable();
            $table->string('warranty_label')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('default_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();

            $table->foreign('brand_id')->references('id')->on('brand')->nullOnDelete();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'product_id']);
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->integer('inventory')->default(0);
            $table->timestamps();
        });

        Schema::create('product_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('vehicle_name');
            $table->integer('year_start')->nullable();
            $table->integer('year_end')->nullable();
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('product_compatibilities');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brand');
    }
};
