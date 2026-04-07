<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        $products = Product::query()
            ->active()
            ->with(['defaultCategory', 'variations', 'compatibilities', 'specifications', 'images'])
            ->inRandomOrder()
            ->take(3)
            ->get();

        $featuredProduct = Product::query()
            ->active()
            ->with(['defaultCategory', 'variations', 'compatibilities', 'specifications', 'images'])
            ->inRandomOrder()
            ->first();

        return view('home', [
            'featuredProduct' => $featuredProduct,
            'products' => $products,
        ]);
    }
}
