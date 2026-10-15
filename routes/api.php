<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\MarketController as AdminMarketController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AdminDataController;
use App\Http\Controllers\Api\AuthDataController;
use App\Http\Controllers\Api\CustomerDataController;
use App\Http\Controllers\Api\FarmerDataController;
use App\Http\Controllers\Api\PublicDataController;
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
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;/*
|--------------------------------------------------------------------------
| API for the Angular SPA (same origin, cookie sessions)
|--------------------------------------------------------------------------
|
| Reads      -> dedicated JSON controllers (Api\*DataController).
| Mutations  -> reuse the existing Blade controllers: their redirect+flash
|               responses are converted to { ok, flash, redirect } JSON by
|               App\Http\Middleware\JsonFlashBridge.
| Sessions   -> Sanctum's stateful-SPA middleware keeps the web session +
|               CSRF stack active for same-origin SPA requests.
|
*/

// ------------------------------------------------------------------ public reads
Route::get('/me', [AuthDataController::class, 'me']);

Route::get('/home', [PublicDataController::class, 'home']);
Route::get('/products', [PublicDataController::class, 'products']);
Route::get('/products/{product}', [PublicDataController::class, 'productShow']);
Route::get('/markets', [PublicDataController::class, 'markets']);
Route::get('/markets/nearby', [\App\Http\Controllers\Api\NearbyMarketController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('api.markets.nearby');
Route::get('/markets/{market}', [PublicDataController::class, 'marketShow']);
Route::get('/markets-map', [MarketController::class, 'mapData']);
Route::get('/farmers', [PublicDataController::class, 'farmers']);
Route::get('/farmers/{farmer}', [PublicDataController::class, 'farmerShow']);

// Public mutations (guest or any role) — bridged to the Blade controllers.
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::post('/password/email', [PasswordResetController::class, 'email']);
Route::post('/password/reset', [PasswordResetController::class, 'update']);
Route::post('/contact', [PageController::class, 'send']);
Route::post('/assistant', [HomeController::class, 'assistant']); // already JSON

Route::get('/product-stock-options/{product}', [ProductController::class, 'stockOptions']);

// ------------------------------------------------------------------ auth required
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'jsonIndex']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    // count served via /me (unread included) — no separate poll needed.

    // ---------------------------------------------------------- customer
    Route::middleware('role:customer')->prefix('customer')->group(function () {
        Route::get('/dashboard', [CustomerDataController::class, 'dashboard']);
        Route::get('/profile', [CustomerDataController::class, 'profile']);
        Route::post('/profile', [CustomerProfileController::class, 'update']);

        Route::get('/cart', [CustomerDataController::class, 'cart']);
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::post('/cart/{item}/update', [CartController::class, 'update']);
        Route::post('/cart/{item}/remove', [CartController::class, 'remove']);

        Route::get('/checkout', [CustomerDataController::class, 'checkout']);
        Route::post('/checkout/place', [CheckoutController::class, 'place']);

        Route::get('/orders', [CustomerDataController::class, 'orders']);
        Route::get('/orders/{order}', [CustomerDataController::class, 'orderShow']);
        Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);
        Route::post('/orders/{order}/reorder', [CustomerOrderController::class, 'reorder']);
        Route::post('/orders/{order}/review/product/{item}', [CustomerOrderController::class, 'reviewProduct']);
        Route::post('/orders/{order}/review/farmer', [CustomerOrderController::class, 'reviewFarmer']);

        Route::get('/favorites', [CustomerDataController::class, 'favorites']);
        Route::post('/favorites/product', [FavoriteController::class, 'toggleProduct']);
        Route::post('/favorites/farmer', [FavoriteController::class, 'toggleFarmer']);
        Route::post('/favorites/market', [FavoriteController::class, 'toggleMarket']);
    });

    // ---------------------------------------------------------- farmer
    Route::middleware('role:farmer')->prefix('farmer')->group(function () {
        Route::get('/dashboard', [FarmerDataController::class, 'dashboard']);
        Route::get('/profile', [FarmerDataController::class, 'profile']);
        Route::post('/profile', [FarmerDashboardController::class, 'updateProfile']);
        Route::get('/stalls', [FarmerDataController::class, 'stalls']);
        Route::post('/stalls', [FarmerDashboardController::class, 'storeStall']);
        Route::post('/stalls/{stall}/toggle', [FarmerDashboardController::class, 'toggleStall']);
        Route::post('/stalls/{stall}/schedule', [FarmerDashboardController::class, 'updateStallSchedule']);

        Route::get('/products', [FarmerDataController::class, 'products']);
        Route::post('/products', [FarmerProductController::class, 'store']);
        Route::post('/products/{product}', [FarmerProductController::class, 'update']);
        Route::delete('/products/{product}', [FarmerProductController::class, 'destroy']);
        Route::post('/products-restore/{id}', [FarmerProductController::class, 'restore']);

        Route::get('/stock', [FarmerDataController::class, 'stock']);
        Route::post('/stock', [StockController::class, 'store']);
        Route::post('/stock/{stock}', [StockController::class, 'update']);
        Route::post('/stock-templates', [StockController::class, 'saveTemplate']);
        Route::post('/stock-templates/apply', [StockController::class, 'applyTemplates']);

        Route::get('/orders', [FarmerDataController::class, 'orders']);
        Route::get('/orders/{order}', [FarmerDataController::class, 'orderShow']);
        Route::post('/orders/{order}/accept', [FarmerOrderController::class, 'accept']);
        Route::post('/orders/{order}/decline', [FarmerOrderController::class, 'decline']);
        Route::post('/orders/{order}/ready', [FarmerOrderController::class, 'markReady']);
        Route::post('/orders/{order}/complete', [FarmerOrderController::class, 'complete']);

        Route::get('/reviews', [FarmerDataController::class, 'reviews']);
        Route::post('/reviews/{review}/reply', [FarmerDashboardController::class, 'replyReview']);

        Route::get('/slots', [FarmerDataController::class, 'slots']);
        Route::post('/slots', [PickupSlotController::class, 'store']);
        Route::post('/slots/{slot}', [PickupSlotController::class, 'update']);
        Route::delete('/slots/{slot}', [PickupSlotController::class, 'destroy']);
    });

    // ---------------------------------------------------------- admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDataController::class, 'dashboard']);
        Route::get('/profile', [AdminDataController::class, 'profile']);
        Route::post('/profile', [AdminProfileController::class, 'update']);

        Route::get('/users', [AdminDataController::class, 'users']);
        Route::post('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
        Route::post('/farmers/{user}/approve', [AdminUserController::class, 'approveFarmer']);
        Route::post('/farmers/{user}/reject', [AdminUserController::class, 'rejectFarmer']);

        Route::get('/markets', [AdminDataController::class, 'markets']);
        Route::post('/markets', [AdminMarketController::class, 'store']);
        Route::post('/markets/{market}', [AdminMarketController::class, 'update']);
        Route::delete('/markets/{market}', [AdminMarketController::class, 'destroy']);

        Route::get('/categories', [AdminDataController::class, 'categories']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::post('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('/feedback', [AdminDataController::class, 'feedback']);
        Route::post('/feedback/{message}/toggle-read', [FeedbackController::class, 'toggleRead']);
        Route::delete('/feedback/{message}', [FeedbackController::class, 'destroy']);

        Route::get('/moderation', [AdminDataController::class, 'moderation']);
        Route::post('/moderation/product/{product}', [ModerationController::class, 'toggleProduct']);
        Route::post('/moderation/product-review/{review}', [ModerationController::class, 'toggleProductReview']);
        Route::post('/moderation/farmer-review/{review}', [ModerationController::class, 'toggleFarmerReview']);

        Route::get('/reports', [AdminDataController::class, 'reports']);

        Route::get('/announcements', [AdminDataController::class, 'announcements']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::post('/announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
    });
});
