<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The app no longer supports teller accounts (see
 * Console\Commands\DeleteTellerAccounts and LoginController's own
 * teller-role block) — every route under the /teller prefix 404s
 * unconditionally, for anyone, logged in or not. Route names/controllers
 * stay in place rather than being deleted outright: other areas (player
 * pages, mainly) still generate teller.* URLs via route() — e.g. a QR
 * code meant for a teller to scan — and those calls would break if the
 * named routes themselves were removed. Visiting the generated link is
 * simply a dead end now, which is the intended end state.
 */
class DisableTellerRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        abort(404);
    }
}
