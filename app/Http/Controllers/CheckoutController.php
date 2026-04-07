<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Models\UserCheckout;
use App\Models\UserPaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, CartController $cartController): View|RedirectResponse
    {
        $cart = $cartController->resolveCart($request);
        $cart->load('cartItems.item.product.images');

        if ($cart->items()->count() < 1) {
            return redirect()->route('cart');
        }

        $selectedItems = $this->selectedCartItems($cart);
        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $userCanContinue = false;
        $userCheckout = null;
        $paymentMethods = collect();

        if ($request->user()) {
            $userCheckout = $this->resolveUserCheckout($request);
            $userCanContinue = true;
            $paymentMethods = $request->user()->paymentMethods()->orderByDesc('is_default')->latest('id')->get();
        } elseif ($request->session()->has('user_checkout_id')) {
            $userCheckout = UserCheckout::query()->find($request->session()->get('user_checkout_id'));
            $userCanContinue = $userCheckout !== null;
        }

        $order = $this->resolveOrder($request, $cart);

        if ($userCheckout) {
            $order->user()->associate($userCheckout);
            $defaultAddress = $order->shippingAddress ?: $userCheckout->addresses()->orderByDesc('is_default')->latest('id')->first();

            if ($defaultAddress && ! $order->shipping_address_id) {
                $order->shippingAddress()->associate($defaultAddress);
                $order->billingAddress()->associate($defaultAddress);
            }

            if (! $order->payment_method && $paymentMethods->isNotEmpty()) {
                $defaultPaymentMethod = $paymentMethods->firstWhere('is_default', true) ?: $paymentMethods->first();
                if ($defaultPaymentMethod) {
                    $order->payment_method = 'prepaid';
                    $order->userPaymentMethod()->associate($defaultPaymentMethod);
                }
            }
        }

        if ($order->payment_method === 'prepaid') {
            $matchedPaymentMethod = $paymentMethods->firstWhere('id', $order->user_payment_method_id);

            if ($matchedPaymentMethod) {
                $order->userPaymentMethod()->associate($matchedPaymentMethod);
            } elseif ($paymentMethods->isNotEmpty()) {
                $fallbackPaymentMethod = $paymentMethods->firstWhere('is_default', true) ?: $paymentMethods->first();
                $order->userPaymentMethod()->associate($fallbackPaymentMethod);
            } else {
                $order->payment_method = 'cod';
                $order->userPaymentMethod()->dissociate();
            }
        }

        $this->syncOrderSnapshot($order, $cart, $selectedItems);

        if ($userCheckout && ! $order->shipping_address_id) {
            return redirect()->route('checkout.address');
        }

        return view('carts.checkout_view', [
            'object' => $cart,
            'order' => $order->fresh(['orderItems', 'shippingAddress', 'userPaymentMethod']),
            'user_can_continue' => $userCanContinue,
            'client_token' => $order->payment_method === 'prepaid' ? $userCheckout?->client_token : null,
            'next_url' => $request->fullUrl(),
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function guest(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'email2' => ['required', 'same:email'],
        ], [
            'email2.same' => 'Please confirm emails are the same',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (User::query()->where('email', $request->input('email'))->exists()) {
                $validator->errors()->add('email2', 'This User already exists. Please login instead.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator, 'guest')->withInput();
        }

        $userCheckout = UserCheckout::query()->firstOrCreate([
            'email' => $request->input('email'),
        ]);

        $request->session()->put('user_checkout_id', $userCheckout->id);

        return redirect()->route('checkout');
    }

    public function address(Request $request, CartController $cartController): View|RedirectResponse
    {
        $checkout = $this->resolveUserCheckout($request, false);
        if (! $checkout) {
            return redirect()->route('checkout');
        }

        $cart = $cartController->resolveCart($request);
        $cart->load('cartItems.item.product.images');

        $selectedItems = $this->selectedCartItems($cart);
        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $shippingAddresses = $checkout->addresses()->orderByDesc('is_default')->latest('id')->get();

        if ($shippingAddresses->isEmpty()) {
            return redirect()->route('checkout.address.create')->with('info', 'Silakan tambahkan alamat pengiriman terlebih dahulu.');
        }

        $order = $this->resolveOrder($request, $cart);
        $this->syncOrderSnapshot($order, $cart, $selectedItems);

        return view('orders.address_select', [
            'shippingAddresses' => $shippingAddresses,
            'order' => $order,
        ]);
    }

    public function storeAddressSelection(Request $request, CartController $cartController): RedirectResponse
    {
        $checkout = $this->resolveUserCheckout($request, false);
        if (! $checkout) {
            return redirect()->route('checkout');
        }

        $validated = $request->validate([
            'shipping_address' => ['required', 'integer'],
        ]);

        $shipping = $checkout->addresses()->whereKey($validated['shipping_address'])->firstOrFail();
        $cart = $cartController->resolveCart($request);
        $selectedItems = $this->selectedCartItems($cart);

        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $order = $this->resolveOrder($request, $cart);
        $this->syncOrderSnapshot($order, $cart, $selectedItems);
        $order->shippingAddress()->associate($shipping);
        $order->billingAddress()->associate($shipping);
        $order->save();

        return redirect()->route('checkout');
    }

    public function createAddress(Request $request): View|RedirectResponse
    {
        if (! $this->resolveUserCheckout($request, false)) {
            return redirect()->route('checkout');
        }

        return view('orders.address_form');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $checkout = $this->resolveUserCheckout($request, false);
        if (! $checkout) {
            return redirect()->route('checkout');
        }

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:32'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zipcode' => ['required', 'string', 'max:50'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $address = $checkout->addresses()->create([
            'label' => $validated['label'] ?? 'Alamat',
            'recipient_name' => $validated['recipient_name'],
            'phone_number' => $validated['phone_number'],
            'type' => 'shipping',
            'street' => $validated['street'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zipcode' => $validated['zipcode'],
            'is_default' => ! $checkout->addresses()->exists() || ! empty($validated['is_default']),
        ]);

        if ($address->is_default) {
            $checkout->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        return redirect()->route('checkout.address')->with('success', 'Alamat pengiriman berhasil disimpan.');
    }

    public function final(Request $request, CartController $cartController): RedirectResponse
    {
        $cart = $cartController->resolveCart($request);
        $selectedItems = $this->selectedCartItems($cart);
        $order = $this->resolveOrder($request, $cart, false);

        if (! $order || $selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $this->syncOrderSnapshot($order, $cart, $selectedItems);
        $this->applyCheckoutPaymentSelection($request, $order);
        $order->loadMissing(['orderItems', 'shippingAddress', 'userPaymentMethod']);

        if (! $order->shipping_address_id) {
            return redirect()->route('checkout.address')->with('danger', 'Pilih alamat pengiriman terlebih dahulu.');
        }

        if (! $order->payment_method) {
            return redirect()->route('checkout')->with('danger', 'Pilih metode pembayaran terlebih dahulu.');
        }

        if ($order->payment_method === 'prepaid' && ! $order->user_payment_method_id) {
            if ($request->user()) {
                return redirect()
                    ->route('account.settings', ['tab' => 'payments'])
                    ->with('danger', 'Atur metode pembayaran prepaid dulu di Settings sebelum memakai Bayar Sekarang.');
            }

            return redirect()->route('checkout')->with('danger', 'Login dan atur metode pembayaran dulu sebelum memakai Bayar Sekarang.');
        }

        if ($order->payment_method === 'prepaid' && ! $this->paymentGatewayReady($order)) {
            return redirect()->route('checkout')->with('danger', 'Gateway pembayaran prepaid belum aktif. Selesaikan dengan COD dulu atau aktifkan gateway pembayaran.');
        }

        if (! in_array($order->status, ['paid', 'shipped', 'refunded'], true)) {
            try {
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
                        $variation = $cartItem->item()->lockForUpdate()->first();

                        if (! $variation) {
                            continue;
                        }

                        $remainingInventory = (int) ($variation->inventory ?? 0) - (int) $cartItem->quantity;

                        if ($remainingInventory < 0) {
                            throw new \RuntimeException('Stok untuk varian "' . $variation->title . '" tidak mencukupi.');
                        }

                        $variation->inventory = $remainingInventory;
                        $variation->save();
                    }

                    if (! $order->order_id) {
                        $order->order_id = 'SSK-' . strtoupper(Str::random(8));
                    }

                    if ($order->payment_method === 'prepaid') {
                        $order->markCompleted($order->order_id);
                    } else {
                        $order->status = 'created';
                        $order->save();
                    }

                    CartItem::query()->whereKey($lockedItems->modelKeys())->delete();
                });
            } catch (\RuntimeException $exception) {
                return redirect()->route('checkout')->with('danger', $exception->getMessage());
            }
        }

        $cart->refresh()->load('cartItems');
        $request->session()->put('cart_item_count', $cart->items()->count());
        $request->session()->forget(['order_id']);

        $message = $order->payment_method === 'cod'
            ? 'Pesanan COD berhasil dibuat dan item terpilih telah dipindahkan dari keranjang.'
            : 'Pembayaran melalui ' . $order->payment_method_label . ' berhasil diproses dan item terpilih telah dipindahkan dari keranjang.';

        return redirect()->route('orders.show', $order)->with('info', $message);
    }

    protected function selectedCartItems(Cart $cart): Collection
    {
        if ($cart->relationLoaded('cartItems')) {
            return $cart->cartItems
                ->where('is_selected', true)
                ->loadMissing('item.product.images')
                ->values();
        }

        return $cart->selectedCartItems()->with('item.product.images')->get();
    }

    protected function syncOrderSnapshot(Order $order, Cart $cart, Collection $selectedItems): void
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
                'variation_title' => $variation?->title,
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

    protected function applyCheckoutPaymentSelection(Request $request, Order $order): void
    {
        $validated = $request->validate([
            'payment_method' => ['nullable', 'in:cod,prepaid'],
            'user_payment_method_id' => ['nullable', 'integer'],
        ]);

        $paymentMethod = $validated['payment_method'] ?? $order->payment_method;

        if (! $paymentMethod) {
            return;
        }

        if ($paymentMethod === 'cod') {
            $order->payment_method = 'cod';
            $order->userPaymentMethod()->dissociate();
            $order->save();

            return;
        }

        $user = $request->user();
        if (! $user) {
            $order->payment_method = 'prepaid';
            $order->userPaymentMethod()->dissociate();
            $order->save();

            return;
        }

        $selectedPaymentMethod = null;

        if (! empty($validated['user_payment_method_id'])) {
            $selectedPaymentMethod = $user->paymentMethods()->whereKey($validated['user_payment_method_id'])->first();
        } elseif ($order->user_payment_method_id) {
            $selectedPaymentMethod = $user->paymentMethods()->whereKey($order->user_payment_method_id)->first();
        }

        if (! $selectedPaymentMethod) {
            $selectedPaymentMethod = $user->paymentMethods()->orderByDesc('is_default')->latest('id')->first();
        }

        if ($selectedPaymentMethod) {
            $order->payment_method = 'prepaid';
            $order->userPaymentMethod()->associate($selectedPaymentMethod);
        } else {
            $order->payment_method = 'prepaid';
            $order->userPaymentMethod()->dissociate();
        }

        $order->save();
    }

    protected function resolveUserCheckout(Request $request, bool $persistAuthenticated = true): ?UserCheckout
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

    protected function resolveOrder(Request $request, Cart $cart, bool $create = true): ?Order
    {
        $orderId = $request->session()->get('order_id');
        $order = $orderId ? Order::query()->find($orderId) : null;

        if ($order && $order->cart_id === $cart->id && ! in_array($order->status, ['paid', 'shipped', 'refunded'], true)) {
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

    protected function paymentGatewayReady(Order $order): bool
    {
        return filled($order->user?->client_token);
    }
}
