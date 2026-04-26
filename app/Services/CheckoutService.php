<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function resolveUserCheckout(Request $request, bool $persistAuthenticated = true): ?UserCheckout
    {
        if ($request->user()) {
            $userCheckout = UserCheckout::query()->firstOrCreate(
                ['email' => $request->user()->email],
                ['user_id' => $request->user()->id]
            );

            if ($persistAuthenticated) {
                $userCheckout->user_id = $request->user()->id;
                $userCheckout->save();
                $request->session()->put('user_checkout_id', $userCheckout->id);
            }

            return $userCheckout;
        }

        $checkoutId = $request->session()->get('user_checkout_id');

        return $checkoutId ? UserCheckout::query()->find($checkoutId) : null;
    }

    public function resolveOrder(Request $request, Cart $cart, bool $create = true): ?Order
    {
        $orderId = $request->session()->get('order_id');
        $order = $orderId ? Order::query()->find($orderId) : null;

        if ($order && $order->cart_id === $cart->id && ! $this->isFinalizedStatus($order->status)) {
            return $order;
        }

        $order = Order::query()
            ->where('cart_id', $cart->id)
            ->whereNotIn('status', ['paid', 'shipped', 'refunded'])
            ->latest('id')
            ->first();

        if (! $order && $create) {
            $order = Order::query()->create([
                'cart_id' => $cart->id,
                'status' => 'draft',
                'payment_method' => 'cod',
            ]);
        }

        if ($order) {
            $request->session()->put('order_id', $order->id);
        }

        return $order;
    }

    public function syncOrderSnapshot(Order $order, Cart $cart, Collection $selectedItems): void
    {
        $order->items_subtotal = round((float) $selectedItems->sum('line_item_total'), 2);
        $order->items_tax_total = round((float) $order->items_subtotal * (float) $cart->tax_percentage, 2);
        $order->items_total = round((float) $order->items_subtotal + (float) $order->items_tax_total, 2);
        $order->save();

        $snapshotPayload = $selectedItems->map(function (CartItem $cartItem) {
            $variation = $cartItem->relationLoaded('item') ? $cartItem->item : $cartItem->item()->with('product.images')->first();
            $product = $variation?->product;
            $unitPrice = (int) $cartItem->quantity > 0
                ? round((float) $cartItem->line_item_total / (int) $cartItem->quantity, 2)
                : 0;

            return [
                'variation_id' => $variation?->id,
                'product_title' => $product?->title ?: 'Produk',
                'variation_title' => null,
                'product_image_url' => $product?->image_url,
                'quantity' => (int) $cartItem->quantity,
                'unit_price' => $unitPrice,
                'line_item_total' => (float) $cartItem->line_item_total,
            ];
        })->all();

        $order->orderItems()->delete();

        if ($snapshotPayload !== []) {
            $order->orderItems()->createMany($snapshotPayload);
        }
    }

    public function finalizeOrder(Order $order, Cart $cart, Collection $selectedItems): void
    {
        DB::transaction(function () use ($order, $cart, $selectedItems) {
            $lockedItems = CartItem::query()
                ->whereKey($selectedItems->modelKeys())
                ->with('item.product.images')
                ->get();

            if ($lockedItems->isEmpty()) {
                throw new \RuntimeException('Pilih minimal satu produk di keranjang untuk lanjut checkout.');
            }

            $this->syncOrderSnapshot($order, $cart, $lockedItems);

            foreach ($lockedItems as $cartItem) {
                $variation = $cartItem->item()->with('product')->first();
                $product = $variation?->product_id
                    ? Product::query()->lockForUpdate()->find($variation->product_id)
                    : null;

                if (! $product) {
                    continue;
                }

                $remainingInventory = (int) ($product->stok ?? 0) - (int) $cartItem->quantity;

                if ($remainingInventory < 0) {
                    if ((int) ($product->stok ?? 0) <= 0) {
                        throw new \RuntimeException('Stok untuk produk "' . $product->title . '" sedang habis. Silakan tunggu admin/staff melakukan restock.');
                    }

                    throw new \RuntimeException('Jumlah untuk produk "' . $product->title . '" melebihi stok tersedia. Saat ini hanya tersedia ' . (int) ($product->stok ?? 0) . ' unit. Silakan kurangi jumlah atau tunggu admin/staff melakukan restock.');
                }

                $product->stok = $remainingInventory;
                $product->save();
            }

            if (! $order->order_id) {
                $order->order_id = 'SSK-' . strtoupper(Str::random(8));
            }

            $order->payment_method = 'cod';
            $order->status = 'created';
            $order->save();

            CartItem::query()->whereKey($lockedItems->modelKeys())->delete();
        });
    }

    protected function isFinalizedStatus(?string $status): bool
    {
        return in_array($status, ['paid', 'shipped', 'refunded'], true);
    }
}
