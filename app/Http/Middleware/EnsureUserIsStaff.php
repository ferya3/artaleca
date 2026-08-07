<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse gate on the whole back office.
 *
 * Policies decide what a signed-in user may do; this decides whether they get
 * through the door at all — so an account that is deactivated after signing in
 * loses access on its very next request rather than at session expiry.
 */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canAccessAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => __('admin.inactive')]);
        }

        return $next($request);
    }
}
