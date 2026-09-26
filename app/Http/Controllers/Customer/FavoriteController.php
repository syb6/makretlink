<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FavoriteFarmer;
use App\Models\FavoriteMarket;
use App\Models\FavoriteProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function index()
    {
        $customerId = Auth::user()->customerProfile->id;

        return view('customer.favorites', [
            'products' => FavoriteProduct::with(['product.farmer.user', 'product.category'])->where('customer_id', $customerId)->get(),
            'farmers' => FavoriteFarmer::with('farmer.user')->where('customer_id', $customerId)->get(),
            'markets' => FavoriteMarket::with('market')->where('customer_id', $customerId)->get(),
        ]);
    }

    public function toggleProduct(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id']]);
        $customerId = Auth::user()->customerProfile->id;

        $existing = FavoriteProduct::where('customer_id', $customerId)->where('product_id', $data['product_id'])->first();

        if ($existing) {
            $existing->delete();
            $state = 'removed';
        } else {
            FavoriteProduct::create(['customer_id' => $customerId, 'product_id' => $data['product_id']]);
            $state = 'added';
        }

        return $request->expectsJson()
            ? response()->json(['state' => $state])
            : back()->with('success', 'Favorite '.$state.'.');
    }

    public function toggleFarmer(Request $request)
    {
        $data = $request->validate(['farmer_id' => ['required', 'exists:farmer_profiles,id']]);
        $customerId = Auth::user()->customerProfile->id;

        $existing = FavoriteFarmer::where('customer_id', $customerId)->where('farmer_id', $data['farmer_id'])->first();

        if ($existing) {
            $existing->delete();
            $state = 'removed';
        } else {
            FavoriteFarmer::create(['customer_id' => $customerId, 'farmer_id' => $data['farmer_id']]);
            $state = 'added';
        }

        return $request->expectsJson()
            ? response()->json(['state' => $state])
            : back()->with('success', 'Favorite '.$state.'.');
    }

    public function toggleMarket(Request $request)
    {
        $data = $request->validate(['market_id' => ['required', 'exists:markets,id']]);
        $customerId = Auth::user()->customerProfile->id;

        $existing = FavoriteMarket::where('customer_id', $customerId)->where('market_id', $data['market_id'])->first();

        if ($existing) {
            $existing->delete();
            $state = 'removed';
        } else {
            FavoriteMarket::create(['customer_id' => $customerId, 'market_id' => $data['market_id']]);
            $state = 'added';
        }

        return $request->expectsJson()
            ? response()->json(['state' => $state])
            : back()->with('success', 'Favorite '.$state.'.');
    }
}
