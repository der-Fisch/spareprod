<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserCheckout;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $checkout = UserCheckout::query()->where('user_id', $request->user()->id)->first();

        return view('orders.order_list', [
            'object_list' => $checkout
                ? Order::query()
                    ->with(['cart.cartItems.item.product', 'orderItems', 'shippingAddress', 'accountUser', 'user'])
                    ->where('user_checkout_id', $checkout->id)
                    ->whereNot('status', 'draft')
                    ->latest('id')
                    ->get()
                : collect(),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $sessionCheckoutId = $request->session()->get('user_checkout_id');
        $authCheckoutId = UserCheckout::query()->where('user_id', $request->user()?->id)->value('id');

        abort_unless(
            $order->user_checkout_id && in_array($order->user_checkout_id, array_filter([$sessionCheckoutId, $authCheckoutId]), true),
            404
        );

        $order->load(['cart.cartItems.item.product', 'orderItems', 'billingAddress', 'shippingAddress', 'user', 'accountUser']);

        return view('orders.order_detail', ['order' => $order]);
    }
}
