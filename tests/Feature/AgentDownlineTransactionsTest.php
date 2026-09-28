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

class AgentDownlineTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private function agent(?User $upline = null): User
    {
        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => 'player']);

        $agent = User::factory()->create(['agent_id' => $upline?->id]);
        $agent->assignRole('agent');
        Wallet::create(['user_id' => $agent->id]);

        return $agent;
    }

    private function player(User $agent): User
    {
        $player = User::factory()->create(['agent_id' => $agent->id]);
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id, 'main_balance' => 1000]);

        return $player;
    }

    private function game(): Game
    {
        return Game::firstOrCreate(['game_name' => 'pool-sabong'], [
            'display_name' => 'Pool Sabong', 'plasada' => 5.00, 'plasada_mode' => 'total_pool',
            'draw_multiplier' => 8.00, 'max_draw_bet' => 100.00, 'min_payout_threshold' => 130.00,
        ]);
    }

    public function test_an_agent_can_view_a_downline_players_transaction_history(): void
    {
        $agent = $this->agent();
        $player = $this->player($agent);

        WalletTransaction::create([
            'wallet_id' => $player->wallet->id, 'type' => 'credit', 'amount' => 500, 'balance_after' => 1500,
            'reference_type' => 'deposit', 'description' => 'Cash deposit via teller Juan',
        ]);

        $response = $this->actingAs($agent)->get(route('agent.downline.transactions', $player));

        $response->assertOk();
        $response->assertSee($player->displayName());
        $response->assertSee('Cash deposit via teller Juan');
    }

    public function test_the_player_downline_page_offers_all_four_tabs(): void
    {
        $agent = $this->agent();
        $player = $this->player($agent);

        $response = $this->actingAs($agent)->get(route('agent.downline.transactions', $player));

        $response->assertOk();
        $response->assertSee(__('Bets'));
        $response->assertSee(__('Deposits'));
        $response->assertSee(__('Withdrawals'));
    }

    public function test_the_bets_tab_only_shows_bet_linked_transactions_for_a_downline_player(): void
    {
        $agent = $this->agent();
        $player = $this->player($agent);

        $event = Event::create(['game_id' => $this->game()->id, 'name' => 'Test Card', 'status' => 'live', 'draw_enabled' => true]);
        $fight = Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'open', 'draw_enabled' => true]);
        app(BettingService::class)->placeBet($player, $fight, 'meron', 50);

        WalletTransaction::create([
            'wallet_id' => $player->wallet->id, 'type' => 'credit', 'amount' => 500, 'balance_after' => 1450,
            'reference_type' => 'deposit', 'description' => 'Cash deposit via teller Juan',
        ]);

        $response = $this->actingAs($agent)->get(route('agent.downline.transactions', [$player, 'tab' => 'bets']));

        $response->assertOk();
        $response->assertSee('Bet on meron for Fight #1');
        $response->assertDontSee('Cash deposit via teller Juan');
    }

    public function test_a_downline_agent_only_gets_all_deposits_withdrawals_tabs(): void
    {
        $agent = $this->agent();
        $subAgent = $this->agent($agent);

        $response = $this->actingAs($agent)->get(route('agent.downline.transactions', $subAgent));

        $response->assertOk();
        $response->assertDontSee(__('Bets'));
        $response->assertSee(__('Deposits'));
        $response->assertSee(__('Withdrawals'));
    }

    public function test_a_bets_tab_request_is_rejected_for_a_downline_agent(): void
    {
        $agent = $this->agent();
        $subAgent = $this->agent($agent);

        $response = $this->actingAs($agent)->get(route('agent.downline.transactions', [$subAgent, 'tab' => 'bets']));

        $response->assertInvalid('tab');
    }

    public function test_an_agent_cannot_view_someone_elses_downline(): void
    {
        $agentA = $this->agent();
        $agentB = $this->agent();
        $playerOfB = $this->player($agentB);

        $response = $this->actingAs($agentA)->get(route('agent.downline.transactions', $playerOfB));

        $response->assertForbidden();
    }

    public function test_an_agent_cannot_view_a_sub_agents_downline_two_levels_deep(): void
    {
        $topAgent = $this->agent();
        $subAgent = $this->agent($topAgent);
        $playerOfSubAgent = $this->player($subAgent);

        $response = $this->actingAs($topAgent)->get(route('agent.downline.transactions', $playerOfSubAgent));

        $response->assertForbidden();
    }

    public function test_the_deposits_tab_includes_admin_topups_for_a_downline_agent(): void
    {
        $agent = $this->agent();
        $subAgent = $this->agent($agent);

        WalletTransaction::create([
            'wallet_id' => $subAgent->wallet->id, 'type' => 'credit', 'amount' => 200, 'balance_after' => 200,
            'reference_type' => 'admin_topup', 'description' => 'Manual top-up by superadmin',
        ]);
        WalletTransaction::create([
            'wallet_id' => $subAgent->wallet->id, 'type' => 'debit', 'amount' => 100, 'balance_after' => 100,
            'reference_type' => 'withdrawal', 'description' => 'Cash withdrawal via teller Juan',
        ]);

        $response = $this->actingAs($agent)->get(route('agent.downline.transactions', [$subAgent, 'tab' => 'deposits']));

        $response->assertOk();
        $response->assertSee('Manual top-up by superadmin');
        $response->assertDontSee('Cash withdrawal via teller Juan');
    }

    public function test_the_agent_dashboard_shows_downline_player_wallet_balances(): void
    {
        $agent = $this->agent();
        $player = $this->player($agent);

        $response = $this->actingAs($agent)->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertSee('1,000.00');
    }

    public function test_the_agent_dashboard_shows_downline_agent_wallet_and_commission_balances(): void
    {
        $agent = $this->agent();
        $subAgent = $this->agent($agent);
        $subAgent->wallet->update(['main_balance' => 250, 'commission_balance' => 75]);

        $response = $this->actingAs($agent)->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertSee('250.00');
        $response->assertSee('75.00');
    }
}
