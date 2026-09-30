<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickupSlot;
use App\Models\WeeklyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Cart::with(['items.product', 'items.farmerMarket.market'])
            ->where('customer_id', Auth::user()->customerProfile->id)
            ->first();

        // No cart yet (customer never added anything) — go back gracefully instead of 404.
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        // Hide anything that is no longer sellable (product deactivated,
        // farmer unapproved, stall/market inactive, week passed, stock gone).
        // Customers fix their cart here instead of hitting errors at place().
        $cart->items->each(function ($item) {
            $item->is_unsellable = ! $this->sellableStockFor($item)->exists();
        });

        $orderable = $cart->items->reject(fn ($i) => $i->is_unsellable);

        if ($orderable->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'The items in your cart are no longer available.');
        }

        // Group orderable items by farmer_market to create one order per stall
        $groups = $orderable->groupBy('farmer_market_id');
        $slots = collect();

        foreach ($groups as $farmerMarketId => $items) {
            $available = PickupSlot::where('farmer_market_id', $farmerMarketId)
                ->where('status', 'active')
                ->where('cutoff_at', '>', now())
                ->orderBy('pickup_date')
                ->orderBy('start_time')
                ->get();

            $slots[$farmerMarketId] = $available;
        }

        return view('customer.checkout', [
            'cart' => $cart,
            'groups' => $groups,
            'slots' => $slots,
            'unsellable' => $cart->items->filter(fn ($i) => $i->is_unsellable),
        ]);
    }

    public function place(Request $request)
    {
        $cart = Cart::with(['items.product', 'items.farmerMarket.farmer.user', 'items.farmerMarket.market'])
            ->where('customer_id', Auth::user()->customerProfile->id)
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'slots' => ['required', 'array'],
            'slots.*' => ['nullable', 'exists:pickup_slots,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = Auth::user()->customerProfile;

        $failures = [];

        $orders = collect(DB::transaction(function () use ($cart, $data, $customer, &$failures) {
            $created = [];
            $orderedFarmerMarketIds = collect();

            foreach ($cart->items->groupBy('farmer_market_id') as $farmerMarketId => $items) {
                $slotId = $data['slots'][$farmerMarketId] ?? null;

                if (! $slotId) {
                    $failures[] = 'Please choose a pickup slot for '.$items->first()->farmerMarket->market->name.'.';

                    continue;
                }

                $slot = PickupSlot::lockForUpdate()->find($slotId);

                if (! $slot || $slot->farmer_market_id !== (int) $farmerMarketId || $slot->status !== 'active' || $slot->cutoff_at->isPast()) {
                    $failures[] = 'That pickup slot is no longer available — please pick another one.';

                    continue;
                }

                // Declined orders must not consume slot capacity.
                if ($slot->max_orders !== null && $slot->orders()->whereNotIn('status', ['cancelled', 'declined'])->count() >= $slot->max_orders) {
                    $failures[] = 'That pickup slot is full — please pick another one.';

                    continue;
                }

                $fm = $items->first()->farmerMarket;

                // ---- Phase 1: lock & verify stock for the WHOLE group ----
                // Nothing is written until every item in the group passes, so
                // a failed group never leaves half-reserved rows behind.
                $stockRows = [];
                $groupOk = true;

                foreach ($items as $item) {
                    // Sellable scope enforces: available status, quantity left,
                    // current/upcoming week, active product, approved farmer,
                    // active stall AND market. Row is locked until commit so
                    // no concurrent checkout can oversell it.
                    $stock = WeeklyStock::sellable()
                        ->where('product_id', $item->product_id)
                        ->where('farmer_market_id', $item->farmer_market_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || (float) $stock->available_quantity < (float) $item->quantity) {
                        $left = $stock ? (float) $stock->available_quantity : 0;
                        $failures[] = $item->product->name.' only has '.$left.' '.$item->product->unit.' left — adjust the quantity in your cart.';
                        $groupOk = false;

                        break;
                    }

                    $stockRows[$item->id] = $stock;
                }

                if (! $groupOk) {
                    continue;
                }

                // ---- Phase 2: write the order, items, reservations ----
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'customer_id' => $customer->id,
                    'farmer_market_id' => $fm->id,
                    'pickup_slot_id' => $slot->id,
                    'customer_name' => Auth::user()->name,
                    'customer_email' => Auth::user()->email,
                    'customer_phone' => Auth::user()->phone,
                    'farmer_name' => $fm->farmer->business_name,
                    'market_name' => $fm->market->name,
                    'pickup_date' => $slot->pickup_date,
                    'pickup_start_time' => $slot->start_time,
                    'pickup_end_time' => $slot->end_time,
                    'status' => 'placed',
                    'total_amount' => 0,
                    'customer_note' => $data['note'] ?? null,
                    'placed_at' => now(),
                ]);

                foreach ($items as $item) {
                    $stock = $stockRows[$item->id];

                    // Charge the LIVE price at order time, never the price
                    // stored when the item was added to the cart — a farmer's
                    // price change must reach old carts immediately.
                    $unitPrice = (float) $stock->product->price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'unit' => $item->product->unit,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => (float) $item->quantity * $unitPrice,
                    ]);

                    $stock->available_quantity = (float) $stock->available_quantity - (float) $item->quantity;
                    if ($stock->available_quantity <= 0) {
                        $stock->available_quantity = 0;
                        $stock->status = 'sold_out';
                    }
                    $stock->save();
                }

                // The stored total always matches the charged prices.
                $order->update(['total_amount' => (float) $order->items()->sum('subtotal')]);

                $order->statusHistories()->create([
                    'status' => 'placed',
                    'changed_by' => Auth::id(),
                    'note' => 'Order placed by customer.',
                    'changed_at' => now(),
                ]);

                // Notify the farmer (customer is the actor, so only the farmer side fires).
                $order->notifyParties('placed', 'New pre-order received.', Auth::user());

                $created[] = $order;
                $orderedFarmerMarketIds->push($fm->id);
            }

            // Only clear the items that were actually ordered — failed groups stay in the cart.
            $orderedItemIds = $cart->items
                ->whereIn('farmer_market_id', $orderedFarmerMarketIds)
                ->pluck('id');

            CartItem::whereIn('id', $orderedItemIds)->delete();

            return $created;
        }));

        if ($orders->isEmpty()) {
            return redirect()->route('checkout.index')->with('error', implode(' ', $failures ?: ['No orders could be placed — please review your pickup slots.']));
        }

        if ($failures) {
            return redirect()
                ->route('orders.index')
                ->with('success', $orders->count().' order(s) placed! Pay at pickup.')
                ->with('error', implode(' ', $failures));
        }

        return redirect()->route('orders.index')->with('success', $orders->count().' order(s) placed! Pay at pickup.');
    }

    /** Live sellable stock row for a cart item (null-safe query builder). */
    private function sellableStockFor(CartItem $item)
    {
        return WeeklyStock::sellable()
            ->where('product_id', $item->product_id)
            ->where('farmer_market_id', $item->farmer_market_id);
    }
}
