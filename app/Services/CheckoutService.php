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
    public function resolveCheckoutUser(Request $request, bool $storeAuthenticatedUser = true): ?UserCheckout
    {
        if ($request->user()) {
            $checkoutUser = UserCheckout::query()->firstOrCreate(
                ['email' => $request->user()->email],
                ['user_id' => $request->user()->id]
            );

            if ($storeAuthenticatedUser) {
                $checkoutUser->user_id = $request->user()->id;
                $checkoutUser->save();
                $request->session()->put('user_checkout_id', $checkoutUser->id);
            }

            return $checkoutUser;
        }

        $checkoutId = $request->session()->get('user_checkout_id');

        return $checkoutId ? UserCheckout::query()->find($checkoutId) : null;
    }

    public function resolveOrder(Request $request, Cart $cart, bool $createIfMissing = true): ?Order
    {
        $idOrder = $request->session()->get('order_id');
        $order = $idOrder ? Order::query()->find($idOrder) : null;

        if ($order && $order->cart_id === $cart->id && ! $this->hasFinalStatus($order->status)) {
            return $order;
        }

        $order = Order::query()
            ->where('cart_id', $cart->id)
            ->whereNotIn('status', ['paid', 'shipped', 'refunded'])
            ->latest('id')
            ->first();

        if (! $order && $createIfMissing) {
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
        $order->items_tax_total = round((float) $order->items_subtotal * (float) $cart->persentasi_pajak, 2);
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
                'product_title' => $product?->judul ?: 'Product',
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
                throw new \RuntimeException('Pilih minimal satu product di cart untuk lanjut checkout.');
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

                $remainingStock = (int) ($product->stok ?? 0) - (int) $cartItem->quantity;

                if ($remainingStock < 0) {
                    if ((int) ($product->stok ?? 0) <= 0) {
                        throw new \RuntimeException('Stok untuk product "' . $product->judul . '" sedang habis. Silakan tunggu admin/staff melakukan restock.');
                    }

                    throw new \RuntimeException('Jumlah untuk product "' . $product->judul . '" melebihi stok tersedia. Saat ini hanya tersedia ' . (int) ($product->stok ?? 0) . ' unit. Silakan kurangi jumlah atau tunggu admin/staff melakukan restock.');
                }

                $product->stok = $remainingStock;
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

    protected function hasFinalStatus(?string $status): bool
    {
        return in_array($status, ['paid', 'shipped', 'refunded'], true);
    }
}
