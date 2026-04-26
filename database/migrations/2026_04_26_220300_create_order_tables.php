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
            $table->string('id_pembelian')->nullable()->unique();
            $table->enum('status', ['draft', 'created', 'paid', 'shipped', 'refunded'])->default('draft');
            $table->string('payment_method')->default('cod');
            $table->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kode_produk')->nullable();
            $table->integer('jumlah')->nullable();
            $table->foreignId('user_checkout_id')->nullable()->constrained('user_checkouts')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->decimal('shipping_total_price', 12, 2)->default(0);
            $table->decimal('items_subtotal', 12, 2)->default(0);
            $table->decimal('items_tax_total', 12, 2)->default(0);
            $table->decimal('items_total', 12, 2)->default(0);
            $table->decimal('order_total', 12, 2)->default(0);
            $table->decimal('total_bayar', 12, 2)->default(0);
            $table->timestamp('tanggal_transaksi')->nullable();
            $table->string('order_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('kode_produk')->references('kode_produk')->on('products')->nullOnDelete();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('variation_id')->nullable()->constrained('variations')->nullOnDelete();
            $table->string('product_title');
            $table->string('variation_title')->nullable();
            $table->string('product_image_url')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_item_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
