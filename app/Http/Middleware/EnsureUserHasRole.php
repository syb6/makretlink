<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict route access to the given role(s), e.g. ->middleware('role:admin')
     * or ->middleware('role:farmer,customer').
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to access this area.');
        }

        if ($user->status !== 'active') {
            auth()->logout();

            return redirect()->route('login')->with('error', 'Your account has been deactivated. Contact the administrator.');
        }

        return $next($request);
    }
}
