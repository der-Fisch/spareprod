<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Variation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $cart = $this->resolveCart($request);
        $itemId = $request->query('item');
        $deleteItem = filter_var($request->query('delete', false), FILTER_VALIDATE_BOOLEAN);
        $flashMessage = '';
        $itemAdded = false;
        $cartItem = null;

        if ($itemId) {
            $variation = Variation::query()->with(['product.images'])->findOrFail($itemId);
            $qty = (int) $request->query('qty', 1);

            if ($qty < 1) {
                $deleteItem = true;
            }

            $cartItem = CartItem::query()->firstOrCreate(
                [
                    'cart_id' => $cart->id,
                    'variation_id' => $variation->id,
                ],
                [
                    'is_selected' => true,
                ]
            );

            if ($cartItem->wasRecentlyCreated) {
                $flashMessage = 'Successfully added to the cart';
                $itemAdded = true;
            }

            if ($deleteItem) {
                $flashMessage = 'Item removed successfully.';
                $removedItemId = $cartItem->id;
                $cartItem->delete();
                $cartItem = null;
            } else {
                if (! $itemAdded) {
                    $flashMessage = 'Quantity has been updated successfully.';
                }

                $cartItem->quantity = $qty;
                $cartItem->is_selected = true;
                $cartItem->setRelation('item', $variation);
                $cartItem->save();
            }

            $cart->refresh()->load('cartItems.item.product.images');
            $this->storeCartCount($request, $cart);

            if ($this->isAjax($request)) {
                return response()->json([
                    'deleted' => $deleteItem,
                    'removed_item_id' => $deleteItem ? ($removedItemId ?? null) : null,
                    'item_added' => $itemAdded,
                    'line_total' => $cartItem?->line_item_total,
                    'subtotal' => $cart->subtotal,
                    'cart_total' => $cart->total,
                    'tax_total' => $cart->tax_total,
                    'selected_subtotal' => $cart->selected_subtotal,
                    'selected_tax_total' => $cart->selected_tax_total,
                    'selected_total' => $cart->selected_total,
                    'selected_count' => $cart->selected_item_count,
                    'all_selected' => $cart->all_items_selected,
                    'flash_message' => $flashMessage,
                    'total_items' => $cart->items()->count(),
                ]);
            }

            return redirect()->route('cart');
        }

        $cart->load('cartItems.item.product.images');
        $this->storeCartCount($request, $cart);

        return view('carts.view', ['object' => $cart]);
    }

    public function updateSelection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'selected' => ['required', 'boolean'],
            'cart_item_ids' => ['required', 'array', 'min:1'],
            'cart_item_ids.*' => ['required', 'integer'],
        ]);

        $cart = $this->resolveCart($request);
        $items = $cart->cartItems()->whereIn('id', $validated['cart_item_ids'])->get();

        foreach ($items as $item) {
            $item->is_selected = (bool) $validated['selected'];
            $item->save();
        }

        $cart->refresh()->load('cartItems.item.product.images');
        $this->storeCartCount($request, $cart);

        return response()->json($this->cartSelectionResponse($cart));
    }

    public function removeSelected(Request $request): RedirectResponse|JsonResponse
    {
        $cart = $this->resolveCart($request);
        $selectedItems = $cart->selectedCartItems()->get();

        foreach ($selectedItems as $item) {
            $item->delete();
        }

        $cart->refresh()->load('cartItems.item.product.images');
        $this->storeCartCount($request, $cart);

        if ($this->isAjax($request)) {
            return response()->json($this->cartSelectionResponse($cart) + [
                'flash_message' => 'Item terpilih berhasil dihapus.',
            ]);
        }

        return redirect()->route('cart')->with('success', 'Item terpilih berhasil dihapus.');
    }

    public function count(Request $request): JsonResponse
    {
        $cartId = $request->session()->get('cart_id');
        $count = 0;

        if ($cartId) {
            $count = Cart::query()->find($cartId)?->items()->count() ?? 0;
        }

        $request->session()->put('cart_item_count', $count);

        return response()->json(['count' => $count]);
    }

    public function resolveCart(Request $request): Cart
    {
        $cartId = $request->session()->get('cart_id');
        $cart = $cartId ? Cart::query()->find($cartId) : null;

        if (! $cart && $request->user()) {
            $cart = Cart::query()
                ->where('user_id', $request->user()->id)
                ->withCount('cartItems')
                ->orderByDesc('cart_items_count')
                ->latest('id')
                ->first();
        }

        if (! $cart) {
            $cart = Cart::query()->create(['tax_percentage' => 0.07500]);
        }

        if ($request->user() && $cart->user_id !== $request->user()->id) {
            $cart->user()->associate($request->user());
            $cart->save();
        }

        $request->session()->put('cart_id', $cart->id);

        return $cart;
    }

    protected function storeCartCount(Request $request, Cart $cart): void
    {
        $request->session()->put('cart_item_count', $cart->items()->count());
    }

    protected function cartSelectionResponse(Cart $cart): array
    {
        return [
            'total_items' => $cart->items()->count(),
            'selected_count' => $cart->selected_item_count,
            'selected_subtotal' => $cart->selected_subtotal,
            'selected_tax_total' => $cart->selected_tax_total,
            'selected_total' => $cart->selected_total,
            'all_selected' => $cart->all_items_selected,
        ];
    }

    protected function isAjax(Request $request): bool
    {
        return $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
