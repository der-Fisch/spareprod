<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->active()
            ->with(['defaultCategory', 'categories', 'compatibilities', 'specifications', 'images']);

        if ($search = trim((string) $request->string('q'))) {
            $products->where(function ($query) use ($search) {
                $query
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($categoryId = $request->integer('category_id')) {
            $products->whereHas('categories', fn ($query) => $query->whereKey($categoryId));
        }

        if ($minPrice = $this->parseCatalogPrice($request->input('min_price'))) {
            $products->where('price', '>=', $minPrice);
        }

        if ($maxPrice = $this->parseCatalogPrice($request->input('max_price'))) {
            $products->where('price', '<=', $maxPrice);
        }

        $paginatedProducts = $products
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'categories' => Category::query()->where('active', true)->orderBy('title')->get(),
            'products' => $paginatedProducts,
            'resultsCount' => $paginatedProducts->total(),
            'filters' => $request->only(['q', 'category_id', 'min_price', 'max_price']),
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['defaultCategory', 'categories', 'compatibilities', 'specifications', 'images']);

        $relatedProducts = Product::query()
            ->active()
            ->with(['defaultCategory', 'compatibilities', 'specifications', 'images'])
            ->whereKeyNot($product->id)
            ->where(function ($query) use ($product) {
                $query->where('default_category_id', $product->default_category_id);

                if ($product->categories->isNotEmpty()) {
                    $query->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('categories.id', $product->categories->pluck('id')));
                }
            })
            ->take(3)
            ->get();

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'cartItemId' => $product->primaryVariation()?->id,
        ]);
    }

    protected function parseCatalogPrice(?string $rawValue): ?float
    {
        if (! $rawValue) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $rawValue);

        if (! $digits) {
            return null;
        }

        return ((float) $digits) / 10000;
    }
}
