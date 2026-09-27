<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FavoriteProduct;
use App\Models\Order;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function dashboard()
    {
        $customerId = Auth::user()->customerProfile->id;

        return view('customer.dashboard', [
            'totalOrders' => Order::where('customer_id', $customerId)->count(),
            'activeOrders' => Order::where('customer_id', $customerId)->whereIn('status', ['placed', 'accepted', 'ready_for_pickup'])->count(),
            'completedOrders' => Order::where('customer_id', $customerId)->where('status', 'completed')->count(),
            'recentOrders' => Order::with(['farmerMarket.market', 'items'])->where('customer_id', $customerId)->latest('placed_at')->take(5)->get(),
            'favoriteCount' => FavoriteProduct::where('customer_id', $customerId)->count(),
            'spent' => (float) Order::where('customer_id', $customerId)->where('status', 'completed')->sum('total_amount'),
        ]);
    }

    public function edit()
    {
        return view('customer.profile', ['user' => Auth::user()->load('customerProfile')]);
    }

    public function update(Request $request, ProfileImageService $images)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
        ]);

        $profileData = ['address' => $data['address']];

        if ($request->hasFile('profile_image')) {
            $profileData['profile_image'] = $images->replace(
                $request->file('profile_image'),
                $user->customerProfile->profile_image,
                'customer-' . $user->customerProfile->id
            );
        }

        $user->customerProfile->update($profileData);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        return back()->with('success', 'Profile updated.');
    }
}
