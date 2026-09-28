<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The webmaster-editable site branding (see Webmaster\CmsSettingsController)
 * — read on every request via SetLocale (already the one middleware that
 * shares whatever every page needs, e.g. currencySymbol) so the layout,
 * favicon, and login page never touch the Setting model directly.
 */
class CmsSettings
{
    public static function current(): array
    {
        return [
            'site_name' => Setting::get('site_name', config('app.name')),
            'logo_url' => Setting::get('site_logo_url'),
            'background_url' => Setting::get('site_background_url'),
            'hide_nav_site_name' => Setting::get('hide_nav_site_name', 'off') === 'on',
        ];
    }
}
