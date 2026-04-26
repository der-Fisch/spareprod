<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('role', ['admin', 'customer'])->default('customer');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_staff')->default(false);
            $table->timestamp('date_joined')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('account_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('whatsapp_number')->nullable();
            $table->string('phone_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->timestamps();
        });

        Schema::create('user_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_checkout_id')->constrained('user_checkouts')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('recipient_name');
            $table->string('phone_number');
            $table->string('type')->default('shipping');
            $table->text('street');
            $table->string('city');
            $table->string('state');
            $table->string('zipcode');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('user_checkouts');
        Schema::dropIfExists('account_profiles');
        Schema::dropIfExists('users');
    }
};
