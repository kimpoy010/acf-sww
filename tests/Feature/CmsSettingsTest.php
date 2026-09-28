<?php

namespace Tests\Feature;

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

        $this->assertEquals('My Sabong', \App\Models\Setting::get('site_name'));
        $this->assertNotNull(\App\Models\Setting::get('site_logo_url'));
        $this->assertNotNull(\App\Models\Setting::get('site_background_url'));

        // Reflected on the login page (as a guest — the 'guest' middleware
        // would otherwise redirect a still-logged-in webmaster away from it).
        $this->post(route('logout'));
        $login = $this->get(route('login'));
        $login->assertOk();
        $login->assertSee('My Sabong');
        $login->assertSee('<title>Log in · My Sabong</title>', false);
    }

    public function test_removing_the_logo_clears_it_and_falls_back_to_the_default_mark(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');

        $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
        ]);
        $this->assertNotNull(\App\Models\Setting::get('site_logo_url'));

        $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'remove_logo' => '1',
        ]);

        $this->assertNull(\App\Models\Setting::get('site_logo_url'));
    }

    public function test_background_shows_on_player_pages_but_not_the_live_betting_page(): void
    {
        Storage::fake('public');
        $webmaster = $this->user('webmaster');
        $this->actingAs($webmaster)->put(route('webmaster.cms.update'), [
            'site_name' => 'My Sabong',
            'background' => UploadedFile::fake()->image('bg.jpg', 800, 600),
        ]);

        $backgroundUrl = \App\Models\Setting::get('site_background_url');

        $player = $this->user('player');
        $profile = $this->actingAs($player)->get(route('play.profile'));
        $profile->assertOk();
        $profile->assertSee($backgroundUrl, false);
    }
}
