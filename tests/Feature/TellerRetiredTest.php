<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\CashTransaction;
use App\Models\Event;
use App\Models\Fight;
use App\Models\Game;
use App\Models\TellerShift;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The app no longer supports teller accounts at all — this covers the
 * three enforcement points (route 404, login block, delete command) as
 * one suite rather than scattering them across the files that used to
 * test the live teller flows those points now shut off.
 */
class TellerRetiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'teller']);
        Role::firstOrCreate(['name' => 'superadmin']);
    }

    private function teller(string $username = 'a_teller'): User
    {
        $teller = User::factory()->create(['username' => $username, 'password' => Hash::make('password')]);
        $teller->assignRole('teller');
        Wallet::create(['user_id' => $teller->id]);

        return $teller;
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        Wallet::create(['user_id' => $admin->id]);

        return $admin;
    }

    public function test_every_teller_route_404s_for_a_guest(): void
    {
        foreach ([
            route('teller.dashboard'),
            route('teller.rfid.index'),
            route('teller.shift.start'),
            route('teller.station.index'),
            route('teller.tickets.create'),
        ] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_every_teller_route_404s_for_an_authenticated_non_teller(): void
    {
        $admin = $this->admin();

        foreach ([
            route('teller.dashboard'),
            route('teller.rfid.index'),
            route('teller.shift.start'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertNotFound();
        }
    }

    public function test_a_teller_login_is_rejected_with_correct_credentials(): void
    {
        $teller = $this->teller();

        $response = $this->post(route('login'), ['login' => $teller->username, 'password' => 'password']);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_an_existing_teller_session_is_signed_out_on_its_next_request(): void
    {
        $teller = $this->teller();

        $this->actingAs($teller)->get(route('play.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_delete_accounts_command_removes_every_teller(): void
    {
        $this->teller('teller_one');
        $this->teller('teller_two');

        $this->artisan('teller:delete-accounts', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, User::role('teller')->count());
    }

    public function test_delete_accounts_command_cascades_teller_shifts_and_nulls_bet_and_cash_transaction_references(): void
    {
        $teller = $this->teller();

        $game = Game::create([
            'game_name' => 'pool-sabong', 'display_name' => 'Pool Sabong', 'plasada' => 5.00,
            'plasada_mode' => 'total_pool', 'draw_multiplier' => 8.00, 'max_draw_bet' => 100.00,
            'min_payout_threshold' => 130.00,
        ]);
        $event = Event::create(['game_id' => $game->id, 'name' => 'Card', 'status' => 'live', 'draw_enabled' => true]);
        $fight = Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'open', 'draw_enabled' => true]);

        $shift = TellerShift::create([
            'teller_id' => $teller->id, 'starting_cash' => 5000, 'status' => 'open', 'started_at' => now(),
        ]);
        $bet = Bet::create([
            'fight_id' => $fight->id, 'side' => 'meron', 'amount' => 100, 'status' => 'matched',
            'placed_by_teller_id' => $teller->id, 'placed_teller_shift_id' => $shift->id,
            'ticket_code' => 'TCK-1',
        ]);
        $cashTransaction = CashTransaction::create([
            'user_id' => User::factory()->create()->id, 'type' => 'deposit', 'amount' => 200,
            'status' => 'completed', 'code' => 'CASH-1', 'teller_id' => $teller->id, 'teller_shift_id' => $shift->id,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->artisan('teller:delete-accounts', ['--force' => true])->assertSuccessful();

        $this->assertModelMissing($teller);
        $this->assertModelMissing($shift);

        // The financial records themselves survive — only the "who" is lost.
        $bet->refresh();
        $this->assertNull($bet->placed_by_teller_id);
        $this->assertNull($bet->placed_teller_shift_id);

        $cashTransaction->refresh();
        $this->assertNull($cashTransaction->teller_id);
        $this->assertNull($cashTransaction->teller_shift_id);
    }

    /**
     * Never expected in real use (a teller's own wallet is never actually
     * transacted against — see DeleteTellerAccounts' own doc comment) but
     * covered here since it's the one case that would otherwise
     * cascade-delete a hash-chained WalletTransaction row.
     */
    public function test_a_teller_with_wallet_transaction_history_is_deactivated_instead_of_deleted(): void
    {
        $teller = $this->teller();
        WalletTransaction::create([
            'wallet_id' => $teller->wallet->id, 'type' => 'credit', 'amount' => 50, 'balance_after' => 50,
            'reference_type' => 'admin_topup', 'description' => 'Unexpected manual credit',
        ]);

        $this->artisan('teller:delete-accounts', ['--force' => true])->assertSuccessful();

        $teller->refresh();
        $this->assertFalse($teller->hasRole('teller'));
        $this->assertSame('inactive', $teller->status);
        $this->assertNotNull($teller->wallet);
        $this->assertSame(1, $teller->wallet->transactions()->count());
    }

    public function test_delete_accounts_command_is_a_noop_when_there_are_no_tellers(): void
    {
        $this->artisan('teller:delete-accounts')->assertSuccessful();
    }

    /**
     * On a fresh environment where only RolesAndUsersSeeder has run (it
     * no longer creates 'teller' at all), the Role row itself doesn't
     * exist yet — User::role('teller') throws Spatie's RoleDoesNotExist
     * in that case, so the command must check for the row first rather
     * than assume it's there.
     */
    public function test_delete_accounts_command_is_a_noop_when_the_teller_role_row_does_not_exist(): void
    {
        Role::where('name', 'teller')->delete();

        $this->artisan('teller:delete-accounts')->assertSuccessful();
    }
}
