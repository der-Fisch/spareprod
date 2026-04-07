<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $profiles = [
        'Ceramic Brake Pad Set' => [
            'product' => [
                'sku' => 'BPS-CER-001',
                'oem_number' => '04465-BZ120',
                'brand_name' => 'Bosch',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Resmi 30 Hari',
                'rating' => 4.8,
                'review_count' => 128,
            ],
            'compatibilities' => [
                ['vehicle_name' => 'Toyota Avanza', 'year_start' => 2015, 'year_end' => 2021, 'sort_order' => 1],
                ['vehicle_name' => 'Daihatsu Xenia', 'year_start' => 2016, 'year_end' => 2022, 'sort_order' => 2],
                ['vehicle_name' => 'Toyota Rush', 'year_start' => 2016, 'year_end' => 2021, 'sort_order' => 3],
            ],
            'specifications' => [
                ['label' => 'Bahan', 'value' => 'Komposit Keramik', 'sort_order' => 1],
                ['label' => 'Posisi', 'value' => 'Depan / Belakang sesuai varian', 'sort_order' => 2],
                ['label' => 'Ketebalan Pad', 'value' => '15 mm', 'sort_order' => 3],
                ['label' => 'Slot Sensor', 'value' => 'Ada', 'sort_order' => 4],
            ],
        ],
        'Ventilated Brake Disc Rotor' => [
            'product' => [
                'sku' => 'ROT-VNT-014',
                'oem_number' => '43512-BZ220',
                'brand_name' => 'Advics',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 14 Hari',
                'rating' => 4.7,
                'review_count' => 74,
            ],
            'compatibilities' => [
                ['vehicle_name' => 'Toyota Avanza', 'year_start' => 2017, 'year_end' => 2022, 'sort_order' => 1],
                ['vehicle_name' => 'Daihatsu Xenia', 'year_start' => 2017, 'year_end' => 2022, 'sort_order' => 2],
            ],
            'specifications' => [
                ['label' => 'Tipe Rotor', 'value' => 'Cakram Ventilated', 'sort_order' => 1],
                ['label' => 'Diameter', 'value' => '258 mm', 'sort_order' => 2],
                ['label' => 'Pola Baut', 'value' => '4 Lubang', 'sort_order' => 3],
                ['label' => 'Lapisan', 'value' => 'Anti Korosi', 'sort_order' => 4],
            ],
        ],
        'Spin-On Oil Filter' => [
            'product' => [
                'sku' => 'OFL-SPN-018',
                'oem_number' => '90915-YZZE1',
                'brand_name' => 'Toyota Genuine Parts',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 7 Hari',
                'rating' => 4.6,
                'review_count' => 89,
            ],
            'compatibilities' => [
                ['vehicle_name' => 'Toyota Avanza', 'year_start' => 2014, 'year_end' => 2021, 'sort_order' => 1],
                ['vehicle_name' => 'Daihatsu Xenia', 'year_start' => 2014, 'year_end' => 2021, 'sort_order' => 2],
                ['vehicle_name' => 'Toyota Calya', 'year_start' => 2016, 'year_end' => 2022, 'sort_order' => 3],
            ],
            'specifications' => [
                ['label' => 'Media Filter', 'value' => 'Campuran Sintetis', 'sort_order' => 1],
                ['label' => 'Ukuran Ulir', 'value' => '3/4-16 UNF', 'sort_order' => 2],
                ['label' => 'Tipe Aliran', 'value' => 'Standar / Heavy Duty', 'sort_order' => 3],
                ['label' => 'Rumah Filter', 'value' => 'Canister Baja', 'sort_order' => 4],
            ],
        ],
        'Panel Air Filter' => [
            'product' => [
                'sku' => 'AIR-PNL-021',
                'oem_number' => '17801-BZ090',
                'brand_name' => 'Denso',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 7 Hari',
                'rating' => 4.5,
                'review_count' => 58,
            ],
            'compatibilities' => [
                ['vehicle_name' => 'Toyota Rush', 'year_start' => 2015, 'year_end' => 2021, 'sort_order' => 1],
                ['vehicle_name' => 'Daihatsu Terios', 'year_start' => 2015, 'year_end' => 2021, 'sort_order' => 2],
            ],
            'specifications' => [
                ['label' => 'Tipe Filter', 'value' => 'Elemen Panel', 'sort_order' => 1],
                ['label' => 'Media', 'value' => 'Serat Lipat', 'sort_order' => 2],
                ['label' => 'Panjang', 'value' => '245 mm', 'sort_order' => 3],
                ['label' => 'Lebar', 'value' => '198 mm', 'sort_order' => 4],
            ],
        ],
        'Iridium Spark Plug' => [
            'product' => [
                'sku' => 'SPK-IRD-006',
                'oem_number' => '90919-01275',
                'brand_name' => 'NGK',
                'brand_type' => 'Aftermarket',
                'warranty_label' => 'Garansi Resmi 30 Hari',
                'rating' => 4.9,
                'review_count' => 164,
            ],
            'compatibilities' => [
                ['vehicle_name' => 'Honda Brio', 'year_start' => 2016, 'year_end' => 2022, 'sort_order' => 1],
                ['vehicle_name' => 'Honda Mobilio', 'year_start' => 2015, 'year_end' => 2021, 'sort_order' => 2],
                ['vehicle_name' => 'Honda BR-V', 'year_start' => 2016, 'year_end' => 2021, 'sort_order' => 3],
            ],
            'specifications' => [
                ['label' => 'Elektroda', 'value' => 'Ujung Iridium', 'sort_order' => 1],
                ['label' => 'Diameter Ulir', 'value' => '12 mm', 'sort_order' => 2],
                ['label' => 'Celah', 'value' => '1.0 mm', 'sort_order' => 3],
                ['label' => 'Rentang Panas', 'value' => '6', 'sort_order' => 4],
            ],
        ],
        'Battery Terminal Clamp' => [
            'product' => [
                'sku' => 'BTC-12V-009',
                'oem_number' => '90982-05035',
                'brand_name' => 'Bosch',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 7 Hari',
                'rating' => 4.6,
                'review_count' => 89,
            ],
            'compatibilities' => [
                ['vehicle_name' => 'Toyota Avanza', 'year_start' => 2015, 'year_end' => 2021, 'sort_order' => 1],
                ['vehicle_name' => 'Daihatsu Xenia', 'year_start' => 2016, 'year_end' => 2022, 'sort_order' => 2],
            ],
            'specifications' => [
                ['label' => 'Bahan', 'value' => 'Paduan Tembaga', 'sort_order' => 1],
                ['label' => 'Tegangan', 'value' => '12V', 'sort_order' => 2],
                ['label' => 'Tipe Clamp', 'value' => 'Universal', 'sort_order' => 3],
                ['label' => 'Finishing', 'value' => 'Lapisan Anti Karat', 'sort_order' => 4],
            ],
        ],
    ];

    public function up(): void
    {
        foreach ($this->profiles as $title => $payload) {
            $product = DB::table('products')->where('title', $title)->first();

            if (! $product) {
                continue;
            }

            DB::table('products')->where('id', $product->id)->update($payload['product']);

            DB::table('product_compatibilities')->where('product_id', $product->id)->delete();
            DB::table('product_specifications')->where('product_id', $product->id)->delete();

            DB::table('product_compatibilities')->insert(
                array_map(function (array $compatibility) use ($product) {
                    return array_merge($compatibility, [
                        'product_id' => $product->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }, $payload['compatibilities'])
            );

            DB::table('product_specifications')->insert(
                array_map(function (array $specification) use ($product) {
                    return array_merge($specification, [
                        'product_id' => $product->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }, $payload['specifications'])
            );
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->profiles) as $title) {
            $product = DB::table('products')->where('title', $title)->first();

            if (! $product) {
                continue;
            }

            DB::table('product_compatibilities')->where('product_id', $product->id)->delete();
            DB::table('product_specifications')->where('product_id', $product->id)->delete();
        }

        DB::table('products')->update([
            'sku' => null,
            'oem_number' => null,
            'brand_name' => null,
            'brand_type' => null,
            'warranty_label' => null,
            'rating' => null,
            'review_count' => 0,
        ]);
    }
};
