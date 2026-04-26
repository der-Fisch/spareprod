<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CartService
{
    public function resolveCart(Request $request): Cart
    {
        $idCart = $request->session()->get('cart_id');
        $cart = $idCart ? Cart::query()->find($idCart) : null;

        if (! $cart && $request->user()) {
            $cart = Cart::query()
                ->where('user_id', $request->user()->id)
                ->withCount('cartItems')
                ->orderByDesc('cart_items_count')
                ->latest('id')
                ->first();
        }

        if (! $cart) {
            $cart = Cart::query()->create(['persentasi_pajak' => 0.07500]);
        }

        if ($request->user() && $cart->user_id !== $request->user()->id) {
            $cart->user()->associate($request->user());
            $cart->save();
        }

        $request->session()->put('cart_id', $cart->id);

        return $cart;
    }

    public function ensureSessionCartOwnsItem(Request $request, CartItem $cartItem): Cart
    {
        $cart = $this->resolveCart($request);

        abort_unless($cartItem->cart_id === $cart->id, 404);

        return $cart;
    }

    public function addItem(Request $request, int $variationId, int $quantity): array
    {
        $cart = $this->resolveCart($request);
        $variation = Variation::query()->with(['product.images'])->findOrFail($variationId);

        $cartItem = CartItem::query()->firstOrCreate(
            [
                'cart_id' => $cart->id,
                'variation_id' => $variation->id,
            ],
            [
                'is_selected' => true,
            ]
        );

        $itemAdded = $cartItem->wasRecentlyCreated;

        $cartItem->quantity = $quantity;
        $cartItem->is_selected = true;
        $cartItem->setRelation('item', $variation);
        $cartItem->save();

        return $this->buildMutationResponse(
            request: $request,
            cart: $cart,
            flashMessage: $itemAdded
                ? 'Product berhasil ditambahkan ke cart.'
                : 'Jumlah product di cart berhasil diperbarui.',
            cartItem: $cartItem,
            itemAdded: $itemAdded
        );
    }

    public function updateItemQuantity(Request $request, CartItem $cartItem, int $quantity): array
    {
        $cart = $this->ensureSessionCartOwnsItem($request, $cartItem);

        if ($quantity < 1) {
            return $this->removeItem($request, $cartItem, $cart);
        }

        $cartItem->loadMissing('item.product.images');
        $cartItem->quantity = $quantity;
        $cartItem->is_selected = true;
        $cartItem->save();

        return $this->buildMutationResponse(
            request: $request,
            cart: $cart,
            flashMessage: 'Jumlah product di cart berhasil diperbarui.',
            cartItem: $cartItem
        );
    }

    public function removeItem(Request $request, CartItem $cartItem, ?Cart $cart = null): array
    {
        $cart ??= $this->ensureSessionCartOwnsItem($request, $cartItem);
        $deletedItemId = $cartItem->id;
        $cartItem->delete();

        return $this->buildMutationResponse(
            request: $request,
            cart: $cart,
            flashMessage: 'Product berhasil dihapus dari cart.',
            deleted: true,
            deletedItemId: $deletedItemId
        );
    }

    public function updateSelection(Request $request, array $cartItemIds, bool $selected): array
    {
        $cart = $this->resolveCart($request);
        $cartItems = $cart->cartItems()->whereIn('id', $cartItemIds)->get();

        foreach ($cartItems as $cartItem) {
            $cartItem->is_selected = $selected;
            $cartItem->save();
        }

        return $this->selectionSummary($request, $cart);
    }

    public function removeSelected(Request $request): array
    {
        $cart = $this->resolveCart($request);
        $selectedItems = $cart->selectedCartItems()->get();

        foreach ($selectedItems as $cartItem) {
            $cartItem->delete();
        }

        return $this->selectionSummary($request, $cart) + [
            'flash_message' => 'Product terpilih berhasil dihapus dari cart.',
        ];
    }

    public function countItems(Request $request): int
    {
        $cartId = $request->session()->get('cart_id');
        $count = 0;

        if ($cartId) {
            $count = Cart::query()->find($cartId)?->items()->count() ?? 0;
        }

        $request->session()->put('cart_item_count', $count);

        return $count;
    }

    public function selectionSummary(Request $request, Cart $cart): array
    {
        $cart->refresh()->load('cartItems.item.product.images');
        $this->storeCartCountInSession($request, $cart);

        return [
            'total_items' => $cart->items()->count(),
            'selected_count' => $cart->selected_item_count,
            'selected_subtotal' => $cart->selected_subtotal,
            'selected_tax_total' => $cart->selected_tax_total,
            'selected_total' => $cart->selected_total,
            'all_selected' => $cart->all_items_selected,
        ];
    }

    public function selectedItems(Cart $cart): Collection
    {
        if ($cart->relationLoaded('cartItems')) {
            return $cart->cartItems
                ->where('is_selected', true)
                ->loadMissing('item.product.images')
                ->values();
        }

        return $cart->selectedCartItems()->with('item.product.images')->get();
    }

    public function itemsWithStockIssues(Collection $cartItems): Collection
    {
        return $cartItems
            ->loadMissing('item.product.images')
            ->filter(fn (CartItem $item) => $item->has_stock_issue)
            ->values();
    }

    public function stockIssueSummary(Collection $cartItems): ?string
    {
        $stockIssues = $this->itemsWithStockIssues($cartItems);

        if ($stockIssues->isEmpty()) {
            return null;
        }

        $primaryMessage = $stockIssues->first()->stock_issue_message;

        if ($stockIssues->count() === 1) {
            return $primaryMessage;
        }

        return $primaryMessage . ' Periksa juga item lain di cart Anda yang mungkin perlu menunggu restock.';
    }

    protected function buildMutationResponse(
        Request $request,
        Cart $cart,
        string $flashMessage,
        ?CartItem $cartItem = null,
        bool $itemAdded = false,
        bool $deleted = false,
        ?int $deletedItemId = null,
    ): array {
        $cart->refresh()->load('cartItems.item.product.images');
        $this->storeCartCountInSession($request, $cart);

        return [
            'deleted' => $deleted,
            'removed_item_id' => $deletedItemId,
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
        ];
    }

    protected function storeCartCountInSession(Request $request, Cart $cart): void
    {
        $request->session()->put('cart_item_count', $cart->items()->count());
    }
}
