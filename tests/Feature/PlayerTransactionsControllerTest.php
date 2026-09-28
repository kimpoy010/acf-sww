<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\Game;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The standalone Transactions page — the full wallet ledger across every
 * reference type, filterable by type and date range. This is what the
 * Wallet page's own "All Transactions" tab used to be before it moved out
 * to its own bottom-nav destination (see WalletController::transactions()
 * and PlayerWalletControllerTest for the three category tabs that remain
 * on the wallet page itself).
 */
class PlayerTransactionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_view_the_full_transaction_ledger(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $wallet = Wallet::create(['user_id' => $player->id, 'main_balance' => 850]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 1000,
            'balance_after' => 1000,
            'reference_type' => 'deposit',
            'description' => 'Cash deposit via teller Juan',
        ]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 150,
            'balance_after' => 850,
            'reference_type' => 'bet',
            'description' => 'Bet on meron for Fight #1',
        ]);

        $response = $this->actingAs($player)->get(route('play.transactions.index'));

        $response->assertOk();
        $response->assertSee('Cash deposit via teller Juan');
        $response->assertSee('Bet on meron for Fight #1');
    }

    /**
     * Deposits and withdrawals (player-initiated or an admin's manual
     * adjustment) get the same short-label treatment as bets — the
     * teller/admin's name in the stored description doesn't need to be on
     * the row itself, and both categories are tinted to match their
     * dedicated deposit/withdrawal icon color.
     */
    public function test_deposit_and_withdrawal_rows_show_a_short_label(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $wallet = Wallet::create(['user_id' => $player->id, 'main_balance' => 1000]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'credit', 'amount' => 500, 'balance_after' => 1500,
            'reference_type' => 'deposit', 'description' => 'Cash deposit via teller Juan Dela Cruz',
        ]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'debit', 'amount' => 200, 'balance_after' => 1300,
            'reference_type' => 'withdrawal', 'description' => 'Cash withdrawal via teller Juan Dela Cruz',
        ]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'credit', 'amount' => 900, 'balance_after' => 2200,
            'reference_type' => 'admin_topup', 'description' => 'Manual top-up by superadmin',
        ]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'debit', 'amount' => 300, 'balance_after' => 1900,
            'reference_type' => 'admin_withdraw', 'description' => 'Manual withdrawal by superadmin',
        ]);

        $response = $this->actingAs($player)->get(route('play.transactions.index'));

        $response->assertOk();
        // The row's visible span (color-tinted by the deposit/withdrawal
        // icon color, not just any "Cash deposit" text elsewhere on the
        // page, like the Type filter's own dropdown option).
        $response->assertSee('style="color: #34d399">Cash deposit</span>', false);
        $response->assertSee('style="color: #f87171">Cash withdrawal</span>', false);
        // The full original sentences are still preserved for the modal.
        $response->assertSee('Cash deposit via teller Juan Dela Cruz');
        $response->assertSee('Cash withdrawal via teller Juan Dela Cruz');
        $response->assertSee('Manual top-up by superadmin');
        $response->assertSee('Manual withdrawal by superadmin');
    }

    public function test_the_type_filter_only_returns_matching_transactions(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $wallet = Wallet::create(['user_id' => $player->id, 'main_balance' => 850]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'credit', 'amount' => 1000, 'balance_after' => 1000,
            'reference_type' => 'deposit', 'description' => 'Cash deposit via teller Juan',
        ]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'debit', 'amount' => 150, 'balance_after' => 850,
            'reference_type' => 'bet', 'description' => 'Bet on meron for Fight #1',
        ]);

        $response = $this->actingAs($player)->get(route('play.transactions.index', ['type' => 'bet']));

        $response->assertOk();
        $response->assertSee('Bet on meron for Fight #1');
        $response->assertDontSee('Cash deposit via teller Juan');
        $transactions = $response->viewData('transactions');
        $this->assertCount(1, $transactions->items());
    }

    public function test_the_date_range_filters_the_ledger(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        $player = User::factory()->create();
        $player->assignRole('player');
        $wallet = Wallet::create(['user_id' => $player->id, 'main_balance' => 1000]);

        $old = WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'debit', 'amount' => 50, 'balance_after' => 950,
            'reference_type' => 'withdrawal', 'description' => 'Old withdrawal',
        ]);
        $old->created_at = now()->subDays(10);
        $old->saveQuietly();

        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'credit', 'amount' => 75, 'balance_after' => 1025,
            'reference_type' => 'deposit', 'description' => 'Recent deposit',
        ]);

        $response = $this->actingAs($player)->get(route('play.transactions.index', [
            'date_from' => now()->subDays(2)->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Recent deposit');
        $response->assertDontSee('Old withdrawal');
    }

    /**
     * A CashTransaction's id can numerically collide with an unrelated
     * Bet's id — the lookup must be gated by reference_type, not just "is
     * reference_id present in the map".
     */
    public function test_a_deposits_reference_id_never_borrows_an_unrelated_bets_event_name(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $wallet = Wallet::create(['user_id' => $player->id, 'main_balance' => 1000]);

        $game = Game::create([
            'game_name' => 'pool-sabong', 'display_name' => 'Pool Sabong', 'plasada' => 5.00,
            'plasada_mode' => 'total_pool', 'draw_multiplier' => 8.00, 'max_draw_bet' => 100.00,
            'min_payout_threshold' => 130.00,
        ]);
        $event = Event::create(['game_id' => $game->id, 'name' => 'Some Card', 'status' => 'live', 'draw_enabled' => true]);
        $fight = Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'open', 'draw_enabled' => true]);
        $bet = app(BettingService::class)->placeBet($player, $fight, 'meron', 20);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 500,
            'balance_after' => 1480,
            'reference_type' => 'deposit',
            'reference_id' => $bet->id,
            'description' => 'Cash deposit via teller Juan',
        ]);

        $response = $this->actingAs($player)->get(route('play.transactions.index'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<button[^>]*data-reference-type="deposit"[^>]*data-event-name=""[^>]*>/s',
            $html,
            "the deposit row's data-event-name must be empty, not borrowed from the bet it numerically collides with"
        );
    }

    public function test_an_ajax_request_returns_only_the_rows_partial(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $wallet = Wallet::create(['user_id' => $player->id, 'main_balance' => 1000]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'credit', 'amount' => 1000, 'balance_after' => 1000,
            'reference_type' => 'deposit', 'description' => 'Cash deposit via teller Juan',
        ]);

        $response = $this->actingAs($player)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('play.transactions.index'));

        $response->assertOk();
        $response->assertSee('Cash deposit via teller Juan');
        $response->assertDontSee('<h1');
    }

    public function test_an_unknown_filter_type_is_rejected(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id, 'main_balance' => 1000]);

        $response = $this->actingAs($player)->get(route('play.transactions.index', ['type' => 'admin_topup']));

        $response->assertSessionHasErrors('type');
    }

    public function test_transactions_page_is_only_accessible_to_players(): void
    {
        Role::firstOrCreate(['name' => 'declarator']);

        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');

        $response = $this->actingAs($declarator)->get(route('play.transactions.index'));

        $response->assertForbidden();
    }
}
