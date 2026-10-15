<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes — Angular SPA shell
|--------------------------------------------------------------------------
| The whole UI is now the Angular app in frontend/ (built to public/spa).
| Laravel serves the SPA at /spa/ to match Angular's production base href.
| The root URL opens the SPA; Angular deep links fall through to the shell.
|--------------------------------------------------------------------------
*/

Route::get('/', SpaController::class)->name('home');
Route::redirect('/spa', '/spa/');

// Keep controller-generated URLs aligned with Angular routes.
Route::get('/login', SpaController::class)->name('login');
Route::get('/register', SpaController::class)->name('register');
Route::get('/reset-password/{token}', SpaController::class)->name('password.reset');
Route::get('/markets', SpaController::class)->name('markets.index');
Route::get('/markets/{market}', SpaController::class)->name('markets.show');
Route::get('/farmers', SpaController::class)->name('farmers.index');
Route::get('/farmers/{farmer}', SpaController::class)->name('farmers.show');
Route::get('/products', SpaController::class)->name('products.index');
Route::get('/products/{product}', SpaController::class)->name('products.show');
Route::get('/about', SpaController::class)->name('about');
Route::get('/contact', SpaController::class)->name('contact');
Route::get('/notifications', SpaController::class)->name('notifications.index');
Route::get('/customer/cart', SpaController::class)->name('cart.index');
Route::get('/customer/checkout', SpaController::class)->name('checkout.index');
Route::get('/customer/orders', SpaController::class)->name('orders.index');
Route::get('/customer/orders/{order}', SpaController::class)->name('orders.show');
Route::get('/customer/dashboard', SpaController::class)->name('customer.dashboard');
Route::get('/farmer/dashboard', SpaController::class)->name('farmer.dashboard');
Route::get('/farmer/orders/{order}', SpaController::class)->name('farmer.orders.show');
Route::get('/admin/dashboard', SpaController::class)->name('admin.dashboard');

Route::get('/{any}', SpaController::class)
    ->where('any', '^(?!api(?:/|$)|up$|storage/|images/|fonts/|css/|js/).*$');
