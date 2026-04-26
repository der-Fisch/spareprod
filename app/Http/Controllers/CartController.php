<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Requests\Cart\UpdateCartSelectionRequest;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {
    }

    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $cart = $this->cartService->resolveCart($request);
        $cart->load('cartItems.item.product.images');
        $request->session()->put('cart_item_count', $cart->items()->count());

        return view('carts.view', ['object' => $cart]);
    }

    public function store(AddCartItemRequest $request): RedirectResponse|JsonResponse
    {
        $response = $this->cartService->addItem(
            $request,
            (int) $request->validated('variation_id'),
            (int) $request->validated('quantity')
        );

        if ($this->isAjax($request)) {
            return response()->json($response);
        }

        return redirect()->route('cart.index')->with('success', $response['flash_message']);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $response = $this->cartService->updateItemQuantity(
            $request,
            $cartItem,
            (int) $request->validated('quantity')
        );

        if ($this->isAjax($request)) {
            return response()->json($response);
        }

        return redirect()->route('cart.index')->with('success', $response['flash_message']);
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $response = $this->cartService->removeItem($request, $cartItem);

        if ($this->isAjax($request)) {
            return response()->json($response);
        }

        return redirect()->route('cart.index')->with('success', $response['flash_message']);
    }

    public function updateSelection(UpdateCartSelectionRequest $request): JsonResponse
    {
        $response = $this->cartService->updateSelection(
            $request,
            $request->validated('cart_item_ids'),
            (bool) $request->validated('selected')
        );

        return response()->json($response);
    }

    public function removeSelected(Request $request): RedirectResponse|JsonResponse
    {
        $response = $this->cartService->removeSelected($request);

        if ($this->isAjax($request)) {
            return response()->json($response);
        }

        return redirect()->route('cart.index')->with('success', $response['flash_message']);
    }

    public function count(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->cartService->countItems($request),
        ]);
    }

    protected function isAjax(Request $request): bool
    {
        return $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
