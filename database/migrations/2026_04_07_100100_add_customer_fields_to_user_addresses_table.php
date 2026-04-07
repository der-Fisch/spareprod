<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->string('label', 80)->nullable()->after('user_checkout_id');
            $table->string('recipient_name', 120)->nullable()->after('label');
            $table->string('phone_number', 32)->nullable()->after('recipient_name');
            $table->boolean('is_default')->default(false)->after('zipcode');
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['label', 'recipient_name', 'phone_number', 'is_default']);
        });
    }
};
