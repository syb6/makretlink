<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\WeeklyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    private function getCart(): Cart
    {
        $customer = Auth::user()->customerProfile;

        return Cart::firstOrCreate(['customer_id' => $customer->id]);
    }

    public function index()
    {
        $cart = $this->getCart()->load(['items.product.farmer.user', 'items.farmerMarket.market']);

        return view('customer.cart', ['cart' => $cart]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'stock_id' => ['required', 'exists:weekly_stocks,id'],
            'quantity' => ['required', 'numeric', 'min:0.5'],
        ]);

        $stock = WeeklyStock::with('product')->findOrFail($data['stock_id']);

        abort_if($stock->status !== 'available' || $stock->available_quantity <= 0, 422, 'This item is not available.');

        $cart = $this->getCart();

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $stock->product_id)
            ->where('farmer_market_id', $stock->farmer_market_id)
            ->first();

        $newQty = ($item?->quantity ?? 0) + (float) $data['quantity'];

        abort_if($newQty > (float) $stock->available_quantity, 422, 'Quantity exceeds available stock ('.$stock->available_quantity.' '.$stock->product->unit.').');

        CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'product_id' => $stock->product_id,
                'farmer_market_id' => $stock->farmer_market_id,
            ],
            [
                'quantity' => $newQty,
                'price' => $stock->product->price,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'count' => Cart::countFor(Auth::user())]);
        }

        return redirect()->route('cart.index')->with('success', 'Added to cart.');
    }

    public function update(Request $request, CartItem $item)
    {
        $this->authorizeItem($item);

        $data = $request->validate(['quantity' => ['required', 'numeric', 'min:0.5']]);

        $stock = WeeklyStock::where('product_id', $item->product_id)
            ->where('farmer_market_id', $item->farmer_market_id)
            ->first();

        if ($stock && (float) $data['quantity'] > (float) $stock->available_quantity) {
            return back()->with('error', 'Only '.$stock->available_quantity.' '.$item->product->unit.' available.');
        }

        $item->update(['quantity' => $data['quantity']]);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(CartItem $item)
    {
        $this->authorizeItem($item);
        $item->delete();

        return back()->with('success', 'Item removed.');
    }

    private function authorizeItem(CartItem $item): void
    {
        abort_unless($item->cart->customer->user_id === Auth::id(), 403);
    }
}
