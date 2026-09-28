<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AuditLogger;
use App\Support\ImageUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Site branding (name/logo/background) — deliberately gated on the
 * webmaster ROLE directly (routes/web.php: role:webmaster), not the
 * RBAC permission system every other superadmin section goes through.
 * Webmaster is this app's one top-level account (see
 * Superadmin\RoleController's own doc comment), and branding the whole
 * site isn't something a scoped-down superadmin should ever be able to
 * do to themselves or, worse, to every other account.
 */
class CmsSettingsController extends Controller
{
    public function edit(): View
    {
        return view('webmaster.cms.edit', [
            'siteName' => Setting::get('site_name', config('app.name')),
            'logoUrl' => Setting::get('site_logo_url'),
            'backgroundUrl' => Setting::get('site_background_url'),
            'hideNavSiteName' => Setting::get('hide_nav_site_name', 'off') === 'on',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:100',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'background' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);

        Setting::set('site_name', $data['site_name']);
        Setting::set('hide_nav_site_name', $request->boolean('hide_nav_site_name') ? 'on' : 'off');

        $logoUrl = Setting::get('site_logo_url');
        if ($request->hasFile('logo')) {
            ImageUpload::deleteIfLocal($logoUrl);
            $logoUrl = ImageUpload::store($request->file('logo'), 'cms');
        } elseif ($request->boolean('remove_logo')) {
            ImageUpload::deleteIfLocal($logoUrl);
            $logoUrl = null;
        }
        Setting::set('site_logo_url', $logoUrl);

        $backgroundUrl = Setting::get('site_background_url');
        if ($request->hasFile('background')) {
            ImageUpload::deleteIfLocal($backgroundUrl);
            $backgroundUrl = ImageUpload::store($request->file('background'), 'cms');
        } elseif ($request->boolean('remove_background')) {
            ImageUpload::deleteIfLocal($backgroundUrl);
            $backgroundUrl = null;
        }
        Setting::set('site_background_url', $backgroundUrl);

        AuditLogger::log(
            action: 'cms.updated',
            description: __('Site branding updated.'),
        );

        return redirect()->route('webmaster.cms.edit')->with('success', __('Site branding updated.'));
    }
}
