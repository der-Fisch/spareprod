<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $productQuery = Product::query()
            ->active()
            ->with(['defaultCategory', 'categories', 'compatibilities', 'specifications', 'images']);

        if ($keyword = trim((string) $request->string('q'))) {
            $productQuery->where(function ($query) use ($keyword) {
                $query
                    ->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }

        if ($idKategori = $request->integer('category_id')) {
            $productQuery->whereHas('categories', fn ($query) => $query->whereKey($idKategori));
        }

        if ($minimumPrice = $this->parseCatalogPrice($request->input('min_price'))) {
            $productQuery->where('price', '>=', $minimumPrice);
        }

        if ($maximumPrice = $this->parseCatalogPrice($request->input('max_price'))) {
            $productQuery->where('price', '<=', $maximumPrice);
        }

        $paginatedProducts = $productQuery
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

        $digitsOnly = preg_replace('/\D+/', '', $rawValue);

        if (! $digitsOnly) {
            return null;
        }

        return ((float) $digitsOnly) / 10000;
    }
}
