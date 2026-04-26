<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkout\FinalizeCheckoutRequest;
use App\Http\Requests\Checkout\GuestCheckoutRequest;
use App\Http\Requests\Checkout\StoreCheckoutAddressRequest;
use App\Http\Requests\Checkout\StoreCheckoutAddressSelectionRequest;
use App\Models\UserCheckout;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
    ) {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->resolveCart($request);
        $cart->load('cartItems.item.product.images');

        if ($cart->items()->count() < 1) {
            return redirect()->route('cart');
        }

        $selectedItems = $this->cartService->selectedItems($cart);
        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $userCanContinue = false;
        $userCheckout = null;
        $paymentMethods = collect();

        if ($request->user()) {
            $userCheckout = $this->checkoutService->resolveUserCheckout($request);
            $userCanContinue = true;
            $paymentMethods = $request->user()->paymentMethods()->orderByDesc('is_default')->latest('id')->get();
        } elseif ($request->session()->has('user_checkout_id')) {
            $userCheckout = UserCheckout::query()->find($request->session()->get('user_checkout_id'));
            $userCanContinue = $userCheckout !== null;
        }

        $order = $this->checkoutService->resolveOrder($request, $cart);

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

        $this->checkoutService->syncOrderSnapshot($order, $cart, $selectedItems);

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

    public function guest(GuestCheckoutRequest $request): RedirectResponse
    {
        $userCheckout = UserCheckout::query()->firstOrCreate([
            'email' => $request->validated('email'),
        ]);

        $request->session()->put('user_checkout_id', $userCheckout->id);

        return redirect()->route('checkout');
    }

    public function address(Request $request): View|RedirectResponse
    {
        $checkout = $this->checkoutService->resolveUserCheckout($request, false);
        if (! $checkout) {
            return redirect()->route('checkout');
        }

        $cart = $this->cartService->resolveCart($request);
        $cart->load('cartItems.item.product.images');

        $selectedItems = $this->cartService->selectedItems($cart);
        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $shippingAddresses = $checkout->addresses()->orderByDesc('is_default')->latest('id')->get();

        if ($shippingAddresses->isEmpty()) {
            return redirect()->route('checkout.address.create')->with('info', 'Silakan tambahkan alamat pengiriman terlebih dahulu.');
        }

        $order = $this->checkoutService->resolveOrder($request, $cart);
        $this->checkoutService->syncOrderSnapshot($order, $cart, $selectedItems);

        return view('orders.address_select', [
            'shippingAddresses' => $shippingAddresses,
            'order' => $order,
        ]);
    }

    public function storeAddressSelection(StoreCheckoutAddressSelectionRequest $request): RedirectResponse
    {
        $checkout = $this->checkoutService->resolveUserCheckout($request, false);
        if (! $checkout) {
            return redirect()->route('checkout');
        }

        $shipping = $checkout->addresses()->whereKey($request->validated('shipping_address'))->firstOrFail();
        $cart = $this->cartService->resolveCart($request);
        $selectedItems = $this->cartService->selectedItems($cart);

        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $order = $this->checkoutService->resolveOrder($request, $cart);
        $this->checkoutService->syncOrderSnapshot($order, $cart, $selectedItems);
        $order->shippingAddress()->associate($shipping);
        $order->billingAddress()->associate($shipping);
        $order->save();

        return redirect()->route('checkout');
    }

    public function createAddress(Request $request): View|RedirectResponse
    {
        if (! $this->checkoutService->resolveUserCheckout($request, false)) {
            return redirect()->route('checkout');
        }

        return view('orders.address_form');
    }

    public function storeAddress(StoreCheckoutAddressRequest $request): RedirectResponse
    {
        $checkout = $this->checkoutService->resolveUserCheckout($request, false);
        if (! $checkout) {
            return redirect()->route('checkout');
        }

        $validated = $request->validated();

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

    public function final(FinalizeCheckoutRequest $request): RedirectResponse
    {
        $cart = $this->cartService->resolveCart($request);
        $selectedItems = $this->cartService->selectedItems($cart);
        $order = $this->checkoutService->resolveOrder($request, $cart, false);

        if (! $order || $selectedItems->isEmpty()) {
            return redirect()->route('cart')->with('info', 'Pilih minimal satu produk di keranjang untuk lanjut checkout.');
        }

        $this->checkoutService->syncOrderSnapshot($order, $cart, $selectedItems);
        $this->checkoutService->applyCheckoutPaymentSelection($request, $order, $request->validated());
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

        if ($order->payment_method === 'prepaid' && ! $this->checkoutService->paymentGatewayReady($order)) {
            return redirect()->route('checkout')->with('danger', 'Gateway pembayaran prepaid belum aktif. Selesaikan dengan COD dulu atau aktifkan gateway pembayaran.');
        }

        if (! in_array($order->status, ['paid', 'shipped', 'refunded'], true)) {
            try {
                $this->checkoutService->finalizeOrder($order, $cart, $selectedItems);
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
}
