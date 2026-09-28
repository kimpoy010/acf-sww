<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlayerBottomTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'player']);
        Role::firstOrCreate(['name' => 'declarator']);
    }

    private function player(): User
    {
        $player = User::factory()->create();
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id, 'main_balance' => 500]);

        return $player;
    }

    public function test_a_player_sees_the_bottom_tab_bar(): void
    {
        $player = $this->player();

        $response = $this->actingAs($player)->get(route('play.index'));

        $response->assertOk();
        $response->assertSee('href="'.route('play.wallet.index').'"', false);
        $response->assertSee('href="'.route('play.profile').'"', false);
        // Cash In/Out is now reached from the Wallet page's own
        // Deposit/Withdraw tabs — Transactions takes its old nav slot.
        $response->assertSee('href="'.route('play.transactions.index').'"', false);
    }

    public function test_a_non_player_does_not_see_the_bottom_tab_bar(): void
    {
        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $response = $this->actingAs($declarator)->get(route('declarator.events.index'));

        $response->assertOk();
        $response->assertDontSee('aria-current="page"', false);
    }

    public function test_a_guest_does_not_see_the_bottom_tab_bar(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertDontSee('href="'.route('play.wallet.index').'"', false);
    }

    public function test_a_player_does_not_see_the_top_nav(): void
    {
        $player = $this->player();

        $response = $this->actingAs($player)->get(route('play.index'));

        $response->assertOk();
        $response->assertDontSee('id="site-nav"', false);
        $response->assertDontSee('id="user-menu"', false);
    }

    public function test_a_non_player_still_sees_the_top_nav(): void
    {
        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $response = $this->actingAs($declarator)->get(route('declarator.events.index'));

        $response->assertOk();
        $response->assertSee('id="site-nav"', false);
    }

    /**
     * Every guest auth page hides the top nav — each carries its own
     * gold-ornament branding header (see auth/login.blade.php and its
     * siblings) that the nav would otherwise duplicate.
     */
    public function test_the_login_page_hides_the_top_nav(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertDontSee('id="site-nav"', false);
    }

    public function test_the_register_page_hides_the_top_nav(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertDontSee('id="site-nav"', false);
    }

    public function test_the_forgot_password_page_hides_the_top_nav(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertDontSee('id="site-nav"', false);
    }

    public function test_the_reset_password_page_hides_the_top_nav(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'x']));

        $response->assertOk();
        $response->assertDontSee('id="site-nav"', false);
    }

    public function test_the_wallet_tab_is_marked_active_on_the_wallet_page(): void
    {
        $player = $this->player();

        $response = $this->actingAs($player)->get(route('play.wallet.index'));

        $response->assertOk();
        // Only one tab should carry aria-current on any given page.
        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="page"'));
    }
}
