<?php

namespace App\Http\Middleware;

use App\Models\Game;
use App\Support\CmsSettings;
use App\Support\GameTheme;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * English only — this app no longer offers a language switcher, so
 * app()->getLocale() always resolves to the config/app.php default. This
 * middleware's real job these days is the other two things it shares on
 * every request:
 *
 * The app is single-tenant (one pool-sabong Game row), so that game's
 * region decides the currency symbol for every page — player and staff
 * alike — that isn't already tied to a specific event/game and so can't
 * pull it from `$event->game->theme()['currency']` itself.
 *
 * It's also the one place that shares the webmaster-editable site
 * branding (name/logo/background — see Webmaster\CmsSettingsController)
 * as `$cms`, for the same reason: every page needs it (title, favicon,
 * nav), and this middleware already runs on every web request.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $region = Game::where('game_name', 'pool-sabong')->value('region');

        View::share('currencySymbol', GameTheme::currencySymbol($region));
        View::share('cms', CmsSettings::current());

        return $next($request);
    }
}
