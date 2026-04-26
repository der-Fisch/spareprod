<?php

use App\Http\Controllers\Account\UserSettingController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('redirect_staff_to_admin')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
    Route::post('/cart/selection', [CartController::class, 'updateSelection'])->name('cart.selection.update');
    Route::post('/cart/remove-selected', [CartController::class, 'removeSelected'])->name('cart.items.remove_selected');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'guest'])->name('checkout.guest');
    Route::get('/checkout/address', [CheckoutController::class, 'address'])->name('checkout.address');
    Route::post('/checkout/address', [CheckoutController::class, 'storeAddressSelection'])->name('checkout.address.store');
    Route::get('/checkout/address/add', [CheckoutController::class, 'createAddress'])->name('checkout.address.create');
    Route::post('/checkout/address/add', [CheckoutController::class, 'storeAddress'])->name('checkout.address.save');
    Route::post('/checkout/final', [CheckoutController::class, 'final'])->name('checkout.final');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/settings', [UserSettingController::class, 'show'])->name('account.settings');
    Route::post('/settings', [UserSettingController::class, 'update'])->name('account.settings.update');
});

Route::middleware(['auth', 'redirect_staff_to_admin'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/{entity}', [AdminController::class, 'index'])->name('entity.list');
    Route::get('/{entity}/{mode}', [AdminController::class, 'modal'])->name('entity.modal.create');
    Route::post('/{entity}/{mode}', [AdminController::class, 'save'])->name('entity.modal.create.store');
    Route::get('/{entity}/{pk}/{mode}', [AdminController::class, 'modal'])->name('entity.modal');
    Route::post('/{entity}/{pk}/{mode}', [AdminController::class, 'save'])->name('entity.modal.store');
});
