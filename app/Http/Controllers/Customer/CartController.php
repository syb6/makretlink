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

        // Auto-remove items that are no longer sellable (product hidden,
        // farmer unapproved, stall/market inactive, week passed). Keeping
        // them would let the totals show money the customer can't spend.
        $removed = 0;
        foreach ($cart->items as $item) {
            $stock = WeeklyStock::sellable()
                ->where('product_id', $item->product_id)
                ->where('farmer_market_id', $item->farmer_market_id)
                ->exists();
            if (! $stock) {
                $item->delete();
                $removed++;
            }
        }
        $cart->refresh()->load(['items.product.farmer.user', 'items.farmerMarket.market']);

        if ($removed > 0) {
            session()->flash('info', $removed.' item(s) removed from your cart — no longer available.');
        }

        return view('customer.cart', ['cart' => $cart]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'stock_id' => ['required', 'exists:weekly_stocks,id'],
            'quantity' => ['required', 'numeric', 'min:0.5'],
        ]);

        // Sellable scope enforces: available status + quantity, current/upcoming
        // week, active product, approved farmer, active stall AND market.
        $stock = WeeklyStock::sellable()->with('product')->find($data['stock_id']);

        if (! $stock) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'This item is no longer available.'], 422);
            }

            return back()->with('error', 'This item is no longer available.');
        }

        $cart = $this->getCart();

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $stock->product_id)
            ->where('farmer_market_id', $stock->farmer_market_id)
            ->first();

        $newQty = ($item?->quantity ?? 0) + (float) $data['quantity'];

        if ($newQty > (float) $stock->available_quantity) {
            $message = 'Quantity exceeds available stock ('.$stock->available_quantity.' '.$stock->product->unit.').';
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'product_id' => $stock->product_id,
                'farmer_market_id' => $stock->farmer_market_id,
            ],
            [
                'quantity' => $newQty,
                // Always mirror the live price — a stale cart price must
                // never survive a farmer's price change.
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

        // Same sellable rules as add() — a hidden/inactive item can't be
        // refreshed in the cart either.
        $stock = WeeklyStock::sellable()
            ->where('product_id', $item->product_id)
            ->where('farmer_market_id', $item->farmer_market_id)
            ->first();

        if (! $stock) {
            return back()->with('error', 'This item is no longer available — please remove it.');
        }

        if ((float) $data['quantity'] > (float) $stock->available_quantity) {
            return back()->with('error', 'Only '.$stock->available_quantity.' '.$item->product->unit.' available.');
        }

        $item->update(['quantity' => $data['quantity'], 'price' => $stock->product->price]);

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
