<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\FarmerMarket;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    private function myOrders()
    {
        $ids = FarmerMarket::where('farmer_id', Auth::user()->farmerProfile->id)->pluck('id');

        return Order::whereIn('farmer_market_id', $ids);
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $orders = $this->myOrders()
            ->with(['items', 'customer.user', 'pickupSlot'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('placed_at')
            ->paginate(15)
            ->withQueryString();

        return view('farmer.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => Order::STATUSES,
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);
        $order->load(['items.product', 'customer.user', 'pickupSlot', 'statusHistories.changedBy']);

        return view('farmer.orders.show', ['order' => $order]);
    }

    public function accept(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'placed') {
            return back()->with('error', 'Only newly placed orders can be accepted.');
        }

        $order->markStatus('accepted', 'Accepted by farmer.', Auth::user());

        return back()->with('success', 'Order accepted.');
    }

    public function decline(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'placed') {
            return back()->with('error', 'Only newly placed orders can be declined.');
        }

        $order->markStatus('declined', 'Declined by farmer.', Auth::user());

        return back()->with('success', 'Order declined.');
    }

    public function markReady(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'accepted') {
            return back()->with('error', 'Accept the order before marking it ready.');
        }

        $order->markStatus('ready_for_pickup', 'Ready for pickup.', Auth::user());

        return back()->with('success', 'Marked as ready for pickup.');
    }

    public function complete(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'ready_for_pickup') {
            return back()->with('error', 'Mark the order ready before completing it.');
        }

        $order->markStatus('completed', 'Picked up and paid in person.', Auth::user());

        return back()->with('success', 'Order completed.');
    }

    private function authorizeOrder(Order $order): void
    {
        $mine = FarmerMarket::where('farmer_id', Auth::user()->farmerProfile->id)->pluck('id');
        abort_unless($order->farmer_market_id && $mine->contains($order->farmer_market_id), 403);
    }
}
