<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method')) {
            DB::table('orders')->update(['payment_method' => 'cod']);
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_payment_method_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_payment_method_id');
            });
        }

        if (Schema::hasTable('user_checkouts') && Schema::hasColumn('user_checkouts', 'braintree_id')) {
            Schema::table('user_checkouts', function (Blueprint $table) {
                $table->dropColumn('braintree_id');
            });
        }

        Schema::dropIfExists('user_payment_methods');
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_payment_methods')) {
            Schema::create('user_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('provider_code', 50);
                $table->string('provider_name', 120);
                $table->string('method_type', 40);
                $table->string('account_name', 120)->nullable();
                $table->string('account_reference', 120)->nullable();
                $table->string('status', 40)->default('active');
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('user_checkouts') && ! Schema::hasColumn('user_checkouts', 'braintree_id')) {
            Schema::table('user_checkouts', function (Blueprint $table) {
                $table->string('braintree_id')->nullable();
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'user_payment_method_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('user_payment_method_id')->nullable()->after('user_checkout_id')->constrained('user_payment_methods')->nullOnDelete();
            });
        }
    }
};
