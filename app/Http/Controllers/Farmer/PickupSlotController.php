<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\FarmerMarket;
use App\Models\PickupSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PickupSlotController extends Controller
{
    private function myFarmerMarketIds(): array
    {
        return FarmerMarket::where('farmer_id', Auth::user()->farmerProfile->id)->pluck('id')->all();
    }

    public function index()
    {
        $slots = PickupSlot::with('farmerMarket.market')
            ->whereIn('farmer_market_id', $this->myFarmerMarketIds())
            ->orderBy('pickup_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('pickup_date');

        return view('farmer.slots.index', [
            'slots' => $slots,
            'farmerMarkets' => FarmerMarket::with('market')->where('farmer_id', Auth::user()->farmerProfile->id)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farmer_market_id' => ['required', 'exists:farmer_markets,id'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'cutoff_at' => ['required', 'date', 'after:now'],
            'max_orders' => ['nullable', 'integer', 'min:1'],
        ]);

        abort_unless(in_array($data['farmer_market_id'], $this->myFarmerMarketIds(), true), 403);

        PickupSlot::create($data + ['status' => 'active']);

        return back()->with('success', 'Pickup slot created.');
    }

    public function update(Request $request, PickupSlot $slot)
    {
        abort_unless(in_array($slot->farmer_market_id, $this->myFarmerMarketIds(), true), 403);

        $data = $request->validate(['status' => ['required', 'in:active,inactive,full']]);

        $slot->update($data);

        return back()->with('success', 'Slot updated.');
    }

    public function destroy(PickupSlot $slot)
    {
        abort_unless(in_array($slot->farmer_market_id, $this->myFarmerMarketIds(), true), 403);
        abort_if($slot->orders()->exists(), 422, 'Slot has orders and cannot be deleted. Set it inactive instead.');

        $slot->delete();

        return back()->with('success', 'Slot removed.');
    }
}
