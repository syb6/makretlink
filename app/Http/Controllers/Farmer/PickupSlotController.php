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
            'end_time' => [
                'required', 'date_format:H:i', 'after:start_time',
                // The cutoff must always land before the pickup window ends —
                // a cutoff after pickup time is meaningless.
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $cutoff = strtotime((string) $request->input('cutoff_at'));
                    $end = strtotime((string) $request->input('pickup_date').' '.$value);
                    if ($cutoff !== false && $end !== false && $cutoff > $end) {
                        $fail('The order cutoff must be before the pickup window ends.');
                    }
                },
            ],
            'cutoff_at' => ['required', 'date', 'after:now'],
            'max_orders' => ['nullable', 'integer', 'min:1'],
        ]);

        abort_unless(in_array((int) $data['farmer_market_id'], $this->myFarmerMarketIds(), true), 403);

        // Friendly validation instead of a raw 500 from the DB unique index
        // (farmer_market_id, pickup_date, start_time, end_time). pickup_date
        // is datetime-cast, so compare on the date part only.
        $duplicate = PickupSlot::where('farmer_market_id', $data['farmer_market_id'])
            ->whereDate('pickup_date', $data['pickup_date'])
            ->where('start_time', $data['start_time'])
            ->where('end_time', $data['end_time'])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'A pickup slot with the same date and times already exists for this stall.');
        }

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
