<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('shipping_total_price', '>', 0)
            ->update([
                'order_total' => DB::raw('CASE WHEN items_total IS NOT NULL THEN items_total ELSE order_total - shipping_total_price END'),
                'total_bayar' => DB::raw('CASE WHEN items_total IS NOT NULL THEN items_total ELSE order_total - shipping_total_price END'),
                'shipping_total_price' => 0,
            ]);
    }

    public function down(): void
    {
        // Ongkir sengaja dihapus dari total order. Tidak ada rollback nilai lama yang aman.
    }
};
