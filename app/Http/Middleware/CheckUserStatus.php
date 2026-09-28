<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->status === 'inactive') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => __('Your account has been deactivated.')]);
        }

        // The app no longer supports teller accounts (see
        // Console\Commands\DeleteTellerAccounts). LoginController already
        // refuses the login itself, but this catches every OTHER way a
        // teller session could exist too — a "remember me" cookie
        // reconstituting one from before this rule existed, a role
        // assigned after login, etc. — on every request, not just at
        // sign-in.
        if ($user && $user->hasRole('teller')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => __('Teller accounts are no longer supported.')]);
        }

        return $next($request);
    }
}
