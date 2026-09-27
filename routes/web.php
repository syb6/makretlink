<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MarketController as AdminMarketController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\FavoriteController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Farmer\OrderController as FarmerOrderController;
use App\Http\Controllers\Farmer\PickupSlotController;
use App\Http\Controllers\Farmer\ProductController as FarmerProductController;
use App\Http\Controllers\Farmer\ReviewController as FarmerReviewController;
use App\Http\Controllers\Farmer\StockController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/assistant', [HomeController::class, 'assistant'])->name('assistant');

Route::get('/markets', [MarketController::class, 'index'])->name('markets.index');
Route::get('/markets/{market}', [MarketController::class, 'show'])->name('markets.show');
Route::get('/markets-map-data', [MarketController::class, 'mapData'])->name('markets.map');

Route::get('/farmers', [FarmerController::class, 'index'])->name('farmers.index');
Route::get('/farmers/{farmer}', [FarmerController::class, 'show'])->name('farmers.show');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'send'])->name('contact.send');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    // Forgot / reset password (tokens expire after 30 minutes)
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| In-app notifications (all roles) — SRS: order updates & pickup readiness
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
});

/*
|--------------------------------------------------------------------------
| Customer area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:customer'])->prefix('customer')->group(function () {
    Route::get('/dashboard', [CustomerProfileController::class, 'dashboard'])->name('customer.dashboard');
    Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/{item}/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/{item}/remove', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');

    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/reorder', [CustomerOrderController::class, 'reorder'])->name('orders.reorder');
    Route::post('/orders/{order}/review/product/{item}', [CustomerOrderController::class, 'reviewProduct'])->name('orders.review.product');
    Route::post('/orders/{order}/review/farmer', [CustomerOrderController::class, 'reviewFarmer'])->name('orders.review.farmer');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/product', [FavoriteController::class, 'toggleProduct'])->name('favorites.toggle.product');
    Route::post('/favorites/farmer', [FavoriteController::class, 'toggleFarmer'])->name('favorites.toggle.farmer');
    Route::post('/favorites/market', [FavoriteController::class, 'toggleMarket'])->name('favorites.toggle.market');
});

// Cart count + add-to-cart also useful from public pages (customer only)
Route::post('/cart/add', [CartController::class, 'add'])->middleware(['auth', 'role:customer'])->name('cart.add.public');

/*
|--------------------------------------------------------------------------
| Farmer area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:farmer'])->prefix('farmer')->group(function () {
    Route::get('/dashboard', [FarmerDashboardController::class, 'index'])->name('farmer.dashboard');
    Route::get('/profile', [FarmerDashboardController::class, 'editProfile'])->name('farmer.profile.edit');
    Route::post('/profile', [FarmerDashboardController::class, 'updateProfile'])->name('farmer.profile.update');
    Route::get('/stalls', [FarmerDashboardController::class, 'stalls'])->name('farmer.stalls.index');
    Route::post('/stalls', [FarmerDashboardController::class, 'storeStall'])->name('farmer.stalls.store');
    Route::post('/stalls/{stall}/toggle', [FarmerDashboardController::class, 'toggleStall'])->name('farmer.stalls.toggle');
    Route::post('/stalls/{stall}/schedule', [FarmerDashboardController::class, 'updateStallSchedule'])->name('farmer.stalls.schedule');
    Route::get('/reviews', [FarmerReviewController::class, 'index'])->name('farmer.reviews');
    Route::post('/reviews/{review}/reply', [FarmerDashboardController::class, 'replyReview'])->name('farmer.reviews.reply');

    Route::get('/products', [FarmerProductController::class, 'index'])->name('farmer.products.index');
    Route::post('/products', [FarmerProductController::class, 'store'])->name('farmer.products.store');
    Route::put('/products/{product}', [FarmerProductController::class, 'update'])->name('farmer.products.update');
    Route::delete('/products/{product}', [FarmerProductController::class, 'destroy'])->name('farmer.products.destroy');
    Route::post('/products/{id}/restore', [FarmerProductController::class, 'restore'])->whereNumber('id')->name('farmer.products.restore');

    Route::get('/stock', [StockController::class, 'index'])->name('farmer.stock.index');
    Route::post('/stock', [StockController::class, 'store'])->name('farmer.stock.store');
    Route::post('/stock/{stock}', [StockController::class, 'update'])->name('farmer.stock.update');
    Route::post('/stock-templates', [StockController::class, 'saveTemplate'])->name('farmer.stock.templates.save');
    Route::post('/stock-templates/apply', [StockController::class, 'applyTemplates'])->name('farmer.stock.templates.apply');

    Route::get('/orders', [FarmerOrderController::class, 'index'])->name('farmer.orders.index');
    Route::get('/orders/{order}', [FarmerOrderController::class, 'show'])->name('farmer.orders.show');
    Route::post('/orders/{order}/accept', [FarmerOrderController::class, 'accept'])->name('farmer.orders.accept');
    Route::post('/orders/{order}/decline', [FarmerOrderController::class, 'decline'])->name('farmer.orders.decline');
    Route::post('/orders/{order}/ready', [FarmerOrderController::class, 'markReady'])->name('farmer.orders.ready');
    Route::post('/orders/{order}/complete', [FarmerOrderController::class, 'complete'])->name('farmer.orders.complete');

    Route::get('/slots', [PickupSlotController::class, 'index'])->name('farmer.slots.index');
    Route::post('/slots', [PickupSlotController::class, 'store'])->name('farmer.slots.store');
    Route::put('/slots/{slot}', [PickupSlotController::class, 'update'])->name('farmer.slots.update');
    Route::delete('/slots/{slot}', [PickupSlotController::class, 'destroy'])->name('farmer.slots.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::post('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('admin.users.status');
    Route::post('/farmers/{user}/approve', [AdminUserController::class, 'approveFarmer'])->name('admin.farmers.approve');
    Route::post('/farmers/{user}/reject', [AdminUserController::class, 'rejectFarmer'])->name('admin.farmers.reject');

    Route::get('/markets', [AdminMarketController::class, 'index'])->name('admin.markets');
    Route::post('/markets', [AdminMarketController::class, 'store'])->name('admin.markets.store');
    Route::put('/markets/{market}', [AdminMarketController::class, 'update'])->name('admin.markets.update');
    Route::delete('/markets/{market}', [AdminMarketController::class, 'destroy'])->name('admin.markets.destroy');

    Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    Route::get('/moderation', [ModerationController::class, 'index'])->name('admin.moderation');
    Route::post('/moderation/product/{product}', [ModerationController::class, 'toggleProduct'])->name('admin.moderation.product');
    Route::post('/moderation/product-review/{review}', [ModerationController::class, 'toggleProductReview'])->name('admin.moderation.productReview');
    Route::post('/moderation/farmer-review/{review}', [ModerationController::class, 'toggleFarmerReview'])->name('admin.moderation.farmerReview');

    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('admin.announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('admin.announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
});
