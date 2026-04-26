<?php

namespace Database\Seeders;

use App\Models\AccountProfile;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCheckout;
use App\Models\Variation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@sparesoko.test',
                'first_name' => 'Admin',
                'last_name' => 'Spare Soko',
                'is_active' => true,
                'is_staff' => true,
                'date_joined' => now()->subMonths(6),
                'password' => Hash::make('password123'),
            ]
        );

        $customer = User::query()->updateOrCreate(
            ['username' => 'raka.saputra'],
            [
                'email' => 'raka@sparesoko.test',
                'first_name' => 'Raka',
                'last_name' => 'Saputra',
                'is_active' => true,
                'is_staff' => false,
                'date_joined' => now()->subMonths(3),
                'password' => Hash::make('password123'),
            ]
        );

        AccountProfile::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'whatsapp_number' => '081234567890',
                'phone_number' => '081234567890',
                'birth_date' => now()->subYears(30)->toDateString(),
                'gender' => 'male',
            ]
        );
        AccountProfile::query()->updateOrCreate(
            ['user_id' => $customer->id],
            [
                'whatsapp_number' => '089876543210',
                'phone_number' => '089876543210',
                'birth_date' => now()->subYears(24)->toDateString(),
                'gender' => 'female',
            ]
        );

        $checkout = UserCheckout::query()->updateOrCreate(
            ['email' => $customer->email],
            ['user_id' => $customer->id]
        );

        $shipping = UserAddress::query()->updateOrCreate(
            ['user_checkout_id' => $checkout->id, 'type' => 'shipping', 'street' => 'Jl. Workshop Utama 22'],
            [
                'label' => 'Rumah',
                'recipient_name' => $customer->name,
                'phone_number' => '089876543210',
                'city' => 'Bekasi',
                'state' => 'Jawa Barat',
                'zipcode' => '17113',
                'is_default' => true,
            ]
        );

        $cart = Cart::query()->updateOrCreate(
            ['user_id' => $customer->id],
            ['tax_percentage' => 0.07500]
        );

        foreach (Variation::query()->take(2)->get() as $index => $variation) {
            CartItem::query()->updateOrCreate(
                ['cart_id' => $cart->id, 'variation_id' => $variation->id],
                ['quantity' => $index + 1]
            );
        }

        $cart->refreshTotals();

        Order::query()->updateOrCreate(
            ['cart_id' => $cart->id],
            [
                'user_checkout_id' => $checkout->id,
                'billing_address_id' => $shipping->id,
                'shipping_address_id' => $shipping->id,
                'payment_method' => 'cod',
                'shipping_total_price' => 5.99,
                'status' => 'created',
                'order_id' => 'SSK-1001',
            ]
        );
    }
}

