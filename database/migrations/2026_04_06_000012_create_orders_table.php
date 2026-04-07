<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status', 32)->default('created');
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_checkout_id')->nullable()->constrained('user_checkouts')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->decimal('shipping_total_price', 12, 2)->default(5.99);
            $table->decimal('order_total', 12, 2)->default(0);
            $table->string('order_id', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
