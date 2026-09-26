<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReview;
use App\Models\FarmerReview;
use App\Models\WeeklyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['items.product', 'pickupSlot', 'farmerMarket.farmer.user'])
            ->where('customer_id', Auth::user()->customerProfile->id)
            ->latest('placed_at')
            ->paginate(10);

        return view('customer.orders.index', ['orders' => $orders]);
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);

        $order->load(['items.product', 'pickupSlot', 'farmerMarket.market', 'farmerMarket.farmer.user', 'statusHistories.changedBy']);

        $existingProductReview = ProductReview::where('order_id', $order->id)->where('customer_id', Auth::user()->customerProfile->id)->get()->keyBy('product_id');
        $existingFarmerReview = FarmerReview::where('order_id', $order->id)->where('customer_id', Auth::user()->customerProfile->id)->first();

        return view('customer.orders.show', [
            'order' => $order,
            'existingProductReview' => $existingProductReview,
            'existingFarmerReview' => $existingFarmerReview,
        ]);
    }

    public function cancel(Order $order)
    {
        $this->authorizeOrder($order);

        if (! in_array($order->status, ['placed', 'accepted'], true)) {
            return back()->with('error', 'This order can no longer be cancelled.');
        }

        $cutoff = $order->pickupSlot?->cutoff_at;
        if ($cutoff && now()->greaterThan($cutoff)) {
            return back()->with('error', 'The cutoff time for this order has passed.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $stock = WeeklyStock::where('product_id', $item->product_id)
                        ->where('farmer_market_id', $order->farmer_market_id)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $stock->available_quantity += (float) $item->quantity;
                        if ($stock->status === 'sold_out' && $stock->available_quantity > 0) {
                            $stock->status = 'available';
                        }
                        $stock->save();
                    }
                }
            }

            $order->markStatus('cancelled', 'Cancelled by customer before cutoff.', Auth::user());
        });

        return back()->with('success', 'Order cancelled.');
    }

    /** Quickly add all items of a past order back into the cart. */
    public function reorder(Order $order)
    {
        $this->authorizeOrder($order);

        $cart = \App\Models\Cart::firstOrCreate(['customer_id' => Auth::user()->customerProfile->id]);
        $added = 0;
        $skipped = 0;

        foreach ($order->items as $item) {
            if (! $item->product_id || ! $order->farmer_market_id) {
                $skipped++;
                continue;
            }

            $stock = WeeklyStock::where('product_id', $item->product_id)
                ->where('farmer_market_id', $order->farmer_market_id)
                ->where('status', 'available')
                ->first();

            if (! $stock || $stock->available_quantity <= 0) {
                $skipped++;
                continue;
            }

            $qty = min((float) $item->quantity, (float) $stock->available_quantity);

            $existing = \App\Models\CartItem::where('cart_id', $cart->id)
                ->where('product_id', $item->product_id)
                ->where('farmer_market_id', $order->farmer_market_id)
                ->first();

            $newQty = min(($existing?->quantity ?? 0) + $qty, (float) $stock->available_quantity);

            \App\Models\CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'product_id' => $item->product_id, 'farmer_market_id' => $order->farmer_market_id],
                ['quantity' => $newQty, 'price' => $item->unit_price]
            );
            $added++;
        }

        return redirect()->route('cart.index')->with('success', $added.' item(s) added to cart'.($skipped ? ", $skipped unavailable" : '').'.');
    }

    public function reviewProduct(Request $request, Order $order, OrderItem $item)
    {
        $this->authorizeOrder($order);
        abort_unless($item->order_id === $order->id, 404);
        abort_unless($order->status === 'completed', 403, 'You can review after the order is completed.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        ProductReview::updateOrCreate(
            ['customer_id' => Auth::user()->customerProfile->id, 'product_id' => $item->product_id, 'order_id' => $order->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'status' => 'visible']
        );

        return back()->with('success', 'Thanks for your review!');
    }

    public function reviewFarmer(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        abort_unless($order->status === 'completed', 403, 'You can review after the order is completed.');
        abort_if(! $order->farmerMarket, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        FarmerReview::updateOrCreate(
            ['customer_id' => Auth::user()->customerProfile->id, 'farmer_id' => $order->farmerMarket->farmer_id, 'order_id' => $order->id],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'status' => 'visible']
        );

        return back()->with('success', 'Thanks for rating this farmer!');
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless($order->customer_id === Auth::user()->customerProfile->id, 403);
    }
}
