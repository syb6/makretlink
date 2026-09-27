<?php

use App\Http\Controllers\Api\NearbyMarketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|-------------------------------------------------------------------------  -
| Public, stateless JSON endpoints (throttled to protect external calls).
*/

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/markets/nearby', [NearbyMarketController::class, 'index'])
        ->name('api.markets.nearby');
});
