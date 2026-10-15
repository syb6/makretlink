<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Session state for the Angular SPA.
 *
 * Auth lifecycle (login/register/logout/password reset) is served by the
 * existing Blade controller methods re-used over /api — the JsonFlashBridge
 * turns their redirect+flash responses into `{ ok, flash, redirect }` JSON.
 * This controller only exposes the current user so the SPA can bootsrap its
 * auth state on load and after any mutation.
 */
class AuthDataController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['user' => null]);
        }

        $user->load(['customerProfile', 'farmerProfile']);

        $customer = $user->customerProfile;
        $farmer = $user->farmerProfile;

        $unread = $user->unreadNotifications()->count();

        $cartCount = 0;
        if ($customer) {
            $cartCount = \App\Models\Cart::countFor($user);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'avatar' => $user->avatarUrl(),
                'customer_profile' => $customer ? array_merge($customer->only(['id', 'address', 'profile_image']), [
                    'cart_count' => $cartCount,
                ]) : null,
                'farmer_profile' => $farmer ? $farmer->only([
                    'id', 'business_name', 'contact_person', 'address', 'description',
                    'profile_image', 'approval_status',
                ]) : null,
            ],
            'unread' => $unread,
        ]);
    }
}
