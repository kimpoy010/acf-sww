<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlayerProfileQrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'player']);
    }

    private function player(string $username = 'juan'): User
    {
        $player = User::factory()->create(['username' => $username]);
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id]);

        return $player;
    }

    public function test_viewing_the_profile_page_generates_a_stable_code(): void
    {
        $player = $this->player();
        $this->assertNull($player->player_code);

        $this->actingAs($player)->get(route('play.profile'))->assertOk();

        $code = $player->fresh()->player_code;
        $this->assertNotEmpty($code);

        // Visiting again must not rotate the code — it's meant to be
        // scanned repeatedly.
        $this->actingAs($player)->get(route('play.profile'))->assertOk();
        $this->assertSame($code, $player->fresh()->player_code);
    }

    public function test_the_profile_page_has_a_logout_button_and_language_switcher(): void
    {
        $player = $this->player();

        $response = $this->actingAs($player)->get(route('play.profile'));

        $response->assertOk();
        $response->assertSee('action="'.route('logout').'"', false);
        $response->assertSee('href="'.route('locale.switch', 'en').'"', false);
        $response->assertSee('href="'.route('locale.switch', 'es').'"', false);
    }

    /**
     * The RFID-card-linking flow this QR code was for (a teller scanning
     * it, per the now-removed teller tests) is retired along with every
     * other teller account (see DisableTellerRoutes) — the link those
     * routes generated is simply a dead end now.
     */
    public function test_the_generated_qr_code_link_now_404s(): void
    {
        $player = $this->player();
        $code = $player->profileCode();

        $this->get(route('teller.rfid.link', $code))->assertNotFound();
    }
}
