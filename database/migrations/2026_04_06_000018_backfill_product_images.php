<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $images = [
        'Ceramic Brake Pad Set' => [
            ['image_path' => 'theme/img/products/ceramic-brake-pad-set.jpg', 'alt_text' => 'Ceramic Brake Pad Set', 'sort_order' => 1],
            ['image_path' => 'theme/img/products/ventilated-brake-disc-rotor.jpg', 'alt_text' => 'Ceramic Brake Pad Set alternate', 'sort_order' => 2],
            ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Ceramic Brake Pad Set workshop scene', 'sort_order' => 3],
        ],
        'Ventilated Brake Disc Rotor' => [
            ['image_path' => 'theme/img/products/ventilated-brake-disc-rotor.jpg', 'alt_text' => 'Ventilated Brake Disc Rotor', 'sort_order' => 1],
            ['image_path' => 'theme/img/products/ceramic-brake-pad-set.jpg', 'alt_text' => 'Ventilated Brake Disc Rotor alternate', 'sort_order' => 2],
            ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Ventilated Brake Disc Rotor workshop scene', 'sort_order' => 3],
        ],
        'Spin-On Oil Filter' => [
            ['image_path' => 'theme/img/products/spin-on-oil-filter.jpg', 'alt_text' => 'Spin-On Oil Filter', 'sort_order' => 1],
            ['image_path' => 'theme/img/products/panel-air-filter.jpg', 'alt_text' => 'Spin-On Oil Filter alternate', 'sort_order' => 2],
            ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Spin-On Oil Filter workshop scene', 'sort_order' => 3],
        ],
        'Panel Air Filter' => [
            ['image_path' => 'theme/img/products/panel-air-filter.jpg', 'alt_text' => 'Panel Air Filter', 'sort_order' => 1],
            ['image_path' => 'theme/img/products/spin-on-oil-filter.jpg', 'alt_text' => 'Panel Air Filter alternate', 'sort_order' => 2],
            ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Panel Air Filter workshop scene', 'sort_order' => 3],
        ],
        'Iridium Spark Plug' => [
            ['image_path' => 'theme/img/products/iridium-spark-plug.jpg', 'alt_text' => 'Iridium Spark Plug', 'sort_order' => 1],
            ['image_path' => 'theme/img/products/battery-terminal-clamp.jpg', 'alt_text' => 'Iridium Spark Plug alternate', 'sort_order' => 2],
            ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Iridium Spark Plug workshop scene', 'sort_order' => 3],
        ],
        'Battery Terminal Clamp' => [
            ['image_path' => 'theme/img/products/battery-terminal-clamp.jpg', 'alt_text' => 'Battery Terminal Clamp', 'sort_order' => 1],
            ['image_path' => 'theme/img/products/iridium-spark-plug.jpg', 'alt_text' => 'Battery Terminal Clamp alternate', 'sort_order' => 2],
            ['image_path' => 'theme/img/marketing1.jpg', 'alt_text' => 'Battery Terminal Clamp workshop scene', 'sort_order' => 3],
        ],
    ];

    public function up(): void
    {
        foreach ($this->images as $title => $items) {
            $product = DB::table('products')->where('title', $title)->first();

            if (! $product) {
                continue;
            }

            DB::table('product_images')->where('product_id', $product->id)->delete();

            DB::table('product_images')->insert(array_map(function (array $item) use ($product) {
                return array_merge($item, [
                    'product_id' => $product->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, $items));
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->images) as $title) {
            $product = DB::table('products')->where('title', $title)->first();

            if ($product) {
                DB::table('product_images')->where('product_id', $product->id)->delete();
            }
        }
    }
};
