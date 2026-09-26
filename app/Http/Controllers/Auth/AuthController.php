<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    public function showRegister(Request $request)
    {
        $role = $request->query('role', 'customer');

        return view('auth.register', ['role' => in_array($role, ['customer', 'farmer'], true) ? $role : 'customer']);
    }

    public function register(Request $request)
    {
        $isFarmer = $request->input('role') === 'farmer';

        $data = $request->validate([
            'role' => ['required', 'in:customer,farmer'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required'],

            // Customer-specific
            'address' => [$isFarmer ? 'nullable' : 'required', 'string', 'max:1000'],

            // Farmer-specific
            'business_name' => [$isFarmer ? 'required' : 'nullable', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'business_address' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'status' => 'active',
            'password' => Hash::make($data['password']),
        ]);

        if ($isFarmer) {
            FarmerProfile::create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'contact_person' => $data['contact_person'] ?? $data['name'],
                'address' => $data['business_address'] ?? $data['address'] ?? '',
                'approval_status' => 'pending',
            ]);
        } else {
            CustomerProfile::create([
                'user_id' => $user->id,
                'address' => $data['address'],
            ]);
        }

        Auth::login($user);

        return $this->redirectByRole($user)->with('success', 'Welcome to MarketLink!');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account has been deactivated. Please contact the administrator.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }

    public static function redirectByRole(User $user)
    {
        return redirect()->route(match (true) {
            $user->isAdmin() => 'admin.dashboard',
            $user->isFarmer() => 'farmer.dashboard',
            default => 'customer.dashboard',
        });
    }
}
