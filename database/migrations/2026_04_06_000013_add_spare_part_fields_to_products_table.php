<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('price');
            $table->string('oem_number')->nullable()->after('sku');
            $table->string('brand_name')->nullable()->after('oem_number');
            $table->string('brand_type')->nullable()->after('brand_name');
            $table->string('warranty_label')->nullable()->after('brand_type');
            $table->decimal('rating', 3, 1)->nullable()->after('warranty_label');
            $table->unsignedInteger('review_count')->default(0)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'oem_number',
                'brand_name',
                'brand_type',
                'warranty_label',
                'rating',
                'review_count',
            ]);
        });
    }
};
