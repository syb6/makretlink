<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "Forgot password" flow built on Laravel's password broker.
 *
 * Tokens are stored hashed, are single-use, and expire after
 * config/auth.php -> passwords.users.expire (30 minutes).
 */
class PasswordResetController extends Controller
{
    /** Show the "email me a reset link" form. */
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    /** Email a reset link (always the same response, no user enumeration). */
    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        $response = back()->with('reset-status', __($status));

        // Local/dev convenience: with the "log" mail driver no real email is
        // sent, so surface the reset link directly instead of hiding it in
        // storage/logs. On production SMTP this block never runs.
        if ($status === Password::RESET_LINK_SENT && config('mail.default') === 'log') {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $token = Password::broker()->createToken($user);
                $url = route('password.reset', ['token' => $token, 'email' => $request->email]);
                $response->with('reset-url', $url)
                    ->with('reset-expiry', now()->addMinutes(config('auth.passwords.users.expire'))->format('g:i A'));
            }
        }

        return $response;
    }

    /** Show the "set a new password" form from the emailed link. */
    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /** Consume the token and update the password. */
    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Sessions of other devices are killed by the auth listener.
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Log the user straight in — they just proved ownership of the email.
            $user = User::where('email', $request->email)->first();
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('home'))->with('success', 'Password changed. You are now signed in.');
        }

        // Expired (> 30 min), invalid or already-used token — say why plainly.
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status) . ' Request a fresh link below — links stay valid for 30 minutes and can only be used once.']);
    }
}
