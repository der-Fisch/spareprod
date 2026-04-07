<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCompatibility;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\Variation;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'brakes' => [
                'title' => 'Brakes',
                'description' => 'Brake pads, rotors, and brake maintenance parts.',
            ],
            'engine' => [
                'title' => 'Engine',
                'description' => 'Engine service items used in real-world vehicle maintenance.',
            ],
            'electrical' => [
                'title' => 'Electrical',
                'description' => 'Ignition and electrical spare parts inspired by common workshop stock.',
            ],
        ];

        $categoryMap = collect($categories)->map(function (array $payload, string $slug) {
            return Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $payload['title'],
                    'description' => $payload['description'],
                    'active' => true,
                ]
            );
        });

        $products = [
            [
                'title' => 'Ceramic Brake Pad Set',
                'description' => 'Ceramic brake pads for everyday passenger vehicles.',
                'price' => 45.00,
                'sku' => 'BPS-CER-001',
                'oem_number' => '04465-BZ120',
                'brand_name' => 'Bosch',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Resmi 30 Hari',
                'rating' => 4.8,
                'default' => 'brakes',
                'categories' => ['brakes'],
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
                'images' => [
                    ['image_path' => 'theme/img/products/ceramic-brake-pad-set.jpg', 'alt_text' => 'Ceramic Brake Pad Set', 'sort_order' => 1],
                    ['image_path' => 'theme/img/products/ventilated-brake-disc-rotor.jpg', 'alt_text' => 'Ceramic Brake Pad Set alternate', 'sort_order' => 2],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Ceramic Brake Pad Set workshop scene', 'sort_order' => 3],
                ],
                'variations' => [
                    ['title' => 'Front Axle Kit', 'price' => 45.00, 'sale_price' => null, 'inventory' => 24],
                    ['title' => 'Rear Axle Kit', 'price' => 42.50, 'sale_price' => 39.90, 'inventory' => 16],
                ],
            ],
            [
                'title' => 'Ventilated Brake Disc Rotor',
                'description' => 'Ventilated front disc rotor with anti-corrosion coating.',
                'price' => 72.50,
                'sku' => 'ROT-VNT-014',
                'oem_number' => '43512-BZ220',
                'brand_name' => 'Advics',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 14 Hari',
                'rating' => 4.7,
                'default' => 'brakes',
                'categories' => ['brakes'],
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
                'images' => [
                    ['image_path' => 'theme/img/products/ventilated-brake-disc-rotor.jpg', 'alt_text' => 'Ventilated Brake Disc Rotor', 'sort_order' => 1],
                    ['image_path' => 'theme/img/products/ceramic-brake-pad-set.jpg', 'alt_text' => 'Ventilated Brake Disc Rotor alternate', 'sort_order' => 2],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Ventilated Brake Disc Rotor workshop scene', 'sort_order' => 3],
                ],
                'variations' => [
                    ['title' => 'Single Rotor', 'price' => 72.50, 'sale_price' => null, 'inventory' => 10],
                ],
            ],
            [
                'title' => 'Spin-On Oil Filter',
                'description' => 'Spin-on oil filter for scheduled engine service.',
                'price' => 12.90,
                'sku' => 'OFL-SPN-018',
                'oem_number' => '90915-YZZE1',
                'brand_name' => 'Toyota Genuine Parts',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 7 Hari',
                'rating' => 4.6,
                'default' => 'engine',
                'categories' => ['engine'],
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
                'images' => [
                    ['image_path' => 'theme/img/products/spin-on-oil-filter.jpg', 'alt_text' => 'Spin-On Oil Filter', 'sort_order' => 1],
                    ['image_path' => 'theme/img/products/panel-air-filter.jpg', 'alt_text' => 'Spin-On Oil Filter alternate', 'sort_order' => 2],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Spin-On Oil Filter workshop scene', 'sort_order' => 3],
                ],
                'variations' => [
                    ['title' => 'Standard Flow', 'price' => 12.90, 'sale_price' => null, 'inventory' => 40],
                    ['title' => 'Heavy Duty', 'price' => 16.50, 'sale_price' => null, 'inventory' => 18],
                ],
            ],
            [
                'title' => 'Panel Air Filter',
                'description' => 'Panel-type air filter for smoother intake airflow.',
                'price' => 18.25,
                'sku' => 'AIR-PNL-021',
                'oem_number' => '17801-BZ090',
                'brand_name' => 'Denso',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 7 Hari',
                'rating' => 4.5,
                'default' => 'engine',
                'categories' => ['engine'],
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
                'images' => [
                    ['image_path' => 'theme/img/products/panel-air-filter.jpg', 'alt_text' => 'Panel Air Filter', 'sort_order' => 1],
                    ['image_path' => 'theme/img/products/spin-on-oil-filter.jpg', 'alt_text' => 'Panel Air Filter alternate', 'sort_order' => 2],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Panel Air Filter workshop scene', 'sort_order' => 3],
                ],
                'variations' => [
                    ['title' => 'Standard Size', 'price' => 18.25, 'sale_price' => null, 'inventory' => 30],
                ],
            ],
            [
                'title' => 'Iridium Spark Plug',
                'description' => 'Iridium-tip spark plug for efficient ignition.',
                'price' => 8.90,
                'sku' => 'SPK-IRD-006',
                'oem_number' => '90919-01275',
                'brand_name' => 'NGK',
                'brand_type' => 'Aftermarket',
                'warranty_label' => 'Garansi Resmi 30 Hari',
                'rating' => 4.9,
                'default' => 'electrical',
                'categories' => ['electrical'],
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
                'images' => [
                    ['image_path' => 'theme/img/products/iridium-spark-plug.jpg', 'alt_text' => 'Iridium Spark Plug', 'sort_order' => 1],
                    ['image_path' => 'theme/img/products/battery-terminal-clamp.jpg', 'alt_text' => 'Iridium Spark Plug alternate', 'sort_order' => 2],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Iridium Spark Plug workshop scene', 'sort_order' => 3],
                ],
                'variations' => [
                    ['title' => 'Single Unit', 'price' => 8.90, 'sale_price' => null, 'inventory' => 60],
                    ['title' => 'Set of Four', 'price' => 33.50, 'sale_price' => 31.00, 'inventory' => 20],
                ],
            ],
            [
                'title' => 'Battery Terminal Clamp',
                'description' => 'Battery terminal clamp with anti-rust finish.',
                'price' => 14.50,
                'sku' => 'BTC-12V-009',
                'oem_number' => '90982-05035',
                'brand_name' => 'Bosch',
                'brand_type' => 'OEM',
                'warranty_label' => 'Garansi Penggantian 7 Hari',
                'rating' => 4.6,
                'default' => 'electrical',
                'categories' => ['electrical'],
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
                'images' => [
                    ['image_path' => 'theme/img/products/battery-terminal-clamp.jpg', 'alt_text' => 'Battery Terminal Clamp', 'sort_order' => 1],
                    ['image_path' => 'theme/img/products/iridium-spark-plug.jpg', 'alt_text' => 'Battery Terminal Clamp alternate', 'sort_order' => 2],
                    ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Battery Terminal Clamp workshop scene', 'sort_order' => 3],
                ],
                'variations' => [
                    ['title' => 'Positive Clamp', 'price' => 14.50, 'sale_price' => null, 'inventory' => 15],
                    ['title' => 'Negative Clamp', 'price' => 14.50, 'sale_price' => null, 'inventory' => 15],
                ],
            ],
        ];

        foreach ($products as $payload) {
            $defaultCategory = $categoryMap->get($payload['default']);
            $brand = Brand::query()->updateOrCreate(
                ['id' => \Illuminate\Support\Str::slug($payload['brand_name'], '_')],
                ['nama_brand' => $payload['brand_name']]
            );

            $product = Product::query()->updateOrCreate(
                ['kode_produk' => $payload['sku']],
                [
                    'title' => $payload['title'],
                    'nama_produk' => $payload['title'],
                    'description' => $payload['description'],
                    'price' => $payload['price'],
                    'harga' => $payload['price'],
                    'sku' => $payload['sku'],
                    'oem_number' => $payload['oem_number'],
                    'brand_id' => $brand->id,
                    'brand_name' => $payload['brand_name'],
                    'brand_type' => $payload['brand_type'],
                    'warranty_label' => $payload['warranty_label'],
                    'rating' => $payload['rating'],
                    'active' => true,
                    'kategori_id' => $defaultCategory?->id,
                    'default_category_id' => $defaultCategory?->id,
                    'tipe_kendaraan' => $payload['compatibilities'][0]['vehicle_name'] ?? 'Universal',
                ]
            );

            $product->categories()->sync(
                collect($payload['categories'])
                    ->map(fn (string $slug) => $categoryMap->get($slug)?->id)
                    ->filter()
                    ->values()
                    ->all()
            );

            foreach ($payload['variations'] as $variationPayload) {
                Variation::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'title' => $variationPayload['title'],
                    ],
                    [
                        'price' => $variationPayload['price'],
                        'sale_price' => $variationPayload['sale_price'],
                        'active' => true,
                        'inventory' => $variationPayload['inventory'],
                    ]
                );
            }

            ProductCompatibility::query()->where('product_id', $product->id)->delete();
            ProductSpecification::query()->where('product_id', $product->id)->delete();
            ProductImage::query()->where('product_id', $product->id)->delete();

            foreach ($payload['compatibilities'] as $compatibilityPayload) {
                ProductCompatibility::query()->create([
                    'product_id' => $product->id,
                    'vehicle_name' => $compatibilityPayload['vehicle_name'],
                    'year_start' => $compatibilityPayload['year_start'],
                    'year_end' => $compatibilityPayload['year_end'],
                    'sort_order' => $compatibilityPayload['sort_order'],
                ]);
            }

            foreach ($payload['specifications'] as $specificationPayload) {
                ProductSpecification::query()->create([
                    'product_id' => $product->id,
                    'label' => $specificationPayload['label'],
                    'value' => $specificationPayload['value'],
                    'sort_order' => $specificationPayload['sort_order'],
                ]);
            }

            foreach ($payload['images'] as $imagePayload) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'image_path' => $imagePayload['image_path'],
                    'alt_text' => $imagePayload['alt_text'],
                    'sort_order' => $imagePayload['sort_order'],
                ]);
            }

            $product->refreshErpSummaryColumns();
        }
    }
}
