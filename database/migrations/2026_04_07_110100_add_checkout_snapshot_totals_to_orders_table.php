<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('items_subtotal', 12, 2)->nullable()->after('shipping_total_price');
            $table->decimal('items_tax_total', 12, 2)->nullable()->after('items_subtotal');
            $table->decimal('items_total', 12, 2)->nullable()->after('items_tax_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['items_subtotal', 'items_tax_total', 'items_total']);
        });
    }
};
