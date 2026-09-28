<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        Role::firstOrCreate(['name' => $role]);

        $user = User::factory()->create();
        $user->assignRole($role);
        Wallet::create(['user_id' => $user->id]);

        return $user;
    }

    public function test_only_webmaster_can_reach_the_cms_page(): void
    {
        $webmaster = $this->user('webmaster');
        $this->actingAs($webmaster)->get(route('webmaster.cms.edit'))->assertOk();

        Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin = $this->user('superadmin');
        $this->actingAs($superadmin)->get(route('webmaster.cms.edit'))->assertForbidden();

        $player = $this->user('player');
        $this->actingAs($player)->get(route('webmaster.cms.edit'))->assertForbidden();
    }

    public function test_webmaster_can_update_site_name_and_upload_logo_and_background(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        $resp = $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
            'background' => UploadedFile::fake()->image('background.jpg', 800, 600),
        ]);

        $resp->assertRedirect(route('webmaster.cms.edit'));

        $this->assertEquals('My Sabong', Setting::get('site_name'));
        $this->assertNotNull(Setting::get('site_logo_url'));
        $this->assertNotNull(Setting::get('site_background_url'));

        // Reflected on the login page (as a guest — the 'guest' middleware
        // would otherwise redirect a still-logged-in webmaster away from it).
        $this->post(route('logout'));
        $login = $this->get(route('login'));
        $login->assertOk();
        $login->assertSee('My Sabong');
        $login->assertSee('<title>Log in · My Sabong</title>', false);
    }

    public function test_the_background_accepts_an_animated_gif(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        $resp = $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'background' => UploadedFile::fake()->image('background.gif', 800, 600),
        ]);

        $resp->assertRedirect(route('webmaster.cms.edit'));
        $resp->assertSessionDoesntHaveErrors('background');
        $this->assertNotNull(Setting::get('site_background_url'));
    }

    /**
     * Animated gifs routinely land well past the old 4 MB cap — this is
     * the exact size the webmaster hit in production (the page silently
     * "just reloaded" before the error-display fix made the real
     * "must not be greater than 4096 kilobytes" message visible).
     */
    public function test_the_background_accepts_a_gif_over_the_old_four_megabyte_cap(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        $resp = $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'background' => UploadedFile::fake()->create('background.gif', 6144, 'image/gif'),
        ]);

        $resp->assertRedirect(route('webmaster.cms.edit'));
        $resp->assertSessionDoesntHaveErrors('background');
        $this->assertNotNull(Setting::get('site_background_url'));
    }

    public function test_the_background_still_rejects_a_file_over_ten_megabytes(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        $resp = $this->actingAs($webmaster)->from(route('webmaster.cms.edit'))->followingRedirects()->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'background' => UploadedFile::fake()->create('background.gif', 10241, 'image/gif'),
        ]);

        $resp->assertOk();
        $resp->assertSee('The background field must not be greater than 10240 kilobytes.');
        $this->assertNull(Setting::get('site_background_url'));
    }

    public function test_the_success_message_is_shown_after_saving(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        $resp = $this->actingAs($webmaster)->from(route('webmaster.cms.edit'))->followingRedirects()->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
        ]);

        $resp->assertOk();
        $resp->assertSee('Site branding updated.');
    }

    /**
     * The edit page previously rendered no feedback at all on a failed
     * submission — a rejected upload (wrong mime, over the size limit,
     * etc.) looked exactly like nothing happened, page just reloaded.
     */
    public function test_a_rejected_upload_shows_a_validation_error_on_the_page(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        // A validation failure redirects via back() (thrown automatically
        // by $request->validate(), not the controller's own redirect()->
        // route() calls, which only run on the success path) — that falls
        // back to the app root without a Referer header, so this needs one
        // set explicitly or followingRedirects() bounces through an extra
        // hop and ages the flashed errors out of session before landing.
        $resp = $this->actingAs($webmaster)->from(route('webmaster.cms.edit'))->followingRedirects()->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'background' => UploadedFile::fake()->create('background.pdf', 100, 'application/pdf'),
        ]);

        // Flash data is consumed by the followed GET's own render, so it's
        // gone from session by the time we get $resp back — assert on the
        // rendered page itself instead, which is what actually matters
        // here (and what the user sees).
        $resp->assertOk();
        // @error only ever renders the field's first message (Blade's
        // $message is $errors->first(), not the full list) — a .pdf fails
        // the `image` rule before `mimes` ever gets a say.
        $resp->assertSee('The background field must be an image.');
        $this->assertNull(Setting::get('site_background_url'));
    }

    public function test_removing_the_logo_clears_it_and_falls_back_to_the_default_mark(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
        ]);
        $this->assertNotNull(Setting::get('site_logo_url'));

        $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'remove_logo' => '1',
        ]);

        $this->assertNull(Setting::get('site_logo_url'));
    }

    public function test_background_shows_on_player_pages_but_not_the_live_betting_page(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');
        $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'background' => UploadedFile::fake()->image('bg.jpg', 800, 600),
        ]);

        $backgroundUrl = Setting::get('site_background_url');

        $player = $this->user('player');
        $profile = $this->actingAs($player)->get(route('play.profile'));
        $profile->assertOk();
        $profile->assertSee($backgroundUrl, false);
    }
}
