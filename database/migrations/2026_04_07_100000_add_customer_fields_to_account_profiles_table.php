<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_profiles', function (Blueprint $table) {
            $table->string('phone_number', 32)->nullable()->after('whatsapp_number');
            $table->date('birth_date')->nullable()->after('phone_number');
            $table->string('gender', 32)->nullable()->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('account_profiles', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'birth_date', 'gender']);
        });
    }
};
