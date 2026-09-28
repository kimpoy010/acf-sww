<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\Game;
use App\Models\User;
use App\Models\VipRebateLog;
use App\Models\VipTier;
use App\Models\Wallet;
use App\Services\BettingService;
use App\Services\VipRebateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VipRebateServiceTest extends TestCase
{
    use RefreshDatabase;

    private Game $game;

    private Fight $fight;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'player']);

        $this->game = Game::create([
            'game_name' => 'pool-sabong',
            'display_name' => 'Pool Sabong',
            'plasada' => 5.00,
            'plasada_mode' => 'losing_side',
            'draw_multiplier' => 8.00,
            'max_draw_bet' => 100.00,
            'min_payout_threshold' => 130.00,
        ]);

        $event = Event::create([
            'game_id' => $this->game->id,
            'name' => 'Test Card',
            'status' => 'live',
            'draw_enabled' => true,
        ]);

        $this->fight = Fight::create([
            'event_id' => $event->id,
            'fight_number' => 1,
            'status' => 'open',
            'draw_enabled' => true,
        ]);

        VipTier::query()->delete();
        VipTier::create(['name' => 'VIP', 'min_valid_bets' => 5000, 'max_valid_bets' => 999999.99, 'rebate_percent' => 0.5]);
        VipTier::create(['name' => 'VIP 1', 'min_valid_bets' => 1000000, 'max_valid_bets' => 4999999.99, 'rebate_percent' => 0.7]);
    }

    private function player(float $balance = 1000): User
    {
        $player = User::factory()->create();
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id, 'main_balance' => $balance]);

        return $player;
    }

    public function test_a_bet_below_the_lowest_tier_earns_no_rebate(): void
    {
        $player = $this->player();
        $bet = Bet::create(['user_id' => $player->id, 'fight_id' => $this->fight->id, 'side' => 'meron', 'amount' => 100, 'status' => 'matched']);

        $rebate = app(VipRebateService::class)->creditRebate($bet, 100);

        $this->assertSame(0.0, $rebate);
        $this->assertEquals(100.00, (float) $player->wallet->fresh()->lifetime_valid_bets);
    }

    public function test_a_bet_that_crosses_the_lowest_threshold_earns_that_tiers_rebate(): void
    {
        $player = $this->player();
        $player->wallet->update(['lifetime_valid_bets' => 4900]);

        $bet = Bet::create(['user_id' => $player->id, 'fight_id' => $this->fight->id, 'side' => 'meron', 'amount' => 200, 'status' => 'matched']);

        $rebate = app(VipRebateService::class)->creditRebate($bet, 200);

        // New total is 5100, crosses into VIP (0.5%) — rebated on the
        // full 200 staked this bet, not just the portion past 5000.
        $this->assertEquals(1.00, $rebate);
        $this->assertEquals(5100.00, (float) $player->wallet->fresh()->lifetime_valid_bets);
        $this->assertEquals(1000 + 1.00, (float) $player->wallet->fresh()->main_balance);
    }

    public function test_a_higher_tier_pays_a_higher_rebate_percentage(): void
    {
        $player = $this->player();
        $player->wallet->update(['lifetime_valid_bets' => 999900]);

        $bet = Bet::create(['user_id' => $player->id, 'fight_id' => $this->fight->id, 'side' => 'wala', 'amount' => 200, 'status' => 'matched']);

        $rebate = app(VipRebateService::class)->creditRebate($bet, 200);

        // New total is 1,000,100 — crosses into VIP 1 (0.7%).
        $this->assertEquals(1.40, $rebate);
    }

    public function test_it_logs_a_vip_rebate_log_row(): void
    {
        $player = $this->player();
        $player->wallet->update(['lifetime_valid_bets' => 5000]);

        $bet = Bet::create(['user_id' => $player->id, 'fight_id' => $this->fight->id, 'side' => 'meron', 'amount' => 100, 'status' => 'matched']);

        app(VipRebateService::class)->creditRebate($bet, 100);

        $this->assertDatabaseHas('vip_rebate_logs', [
            'player_id' => $player->id,
            'bet_id' => $bet->id,
            'side' => 'meron',
            'valid_amount' => 100.00,
        ]);
        $this->assertSame(1, VipRebateLog::count());
    }

    public function test_settlement_credits_rebate_on_both_winning_and_losing_bets(): void
    {
        $meronPlayer = $this->player();
        $meronPlayer->wallet->update(['lifetime_valid_bets' => 5000]);

        $walaPlayer = $this->player();
        $walaPlayer->wallet->update(['lifetime_valid_bets' => 5000]);

        Bet::create(['user_id' => $meronPlayer->id, 'fight_id' => $this->fight->id, 'side' => 'meron', 'amount' => 100, 'status' => 'matched']);
        Bet::create(['user_id' => $walaPlayer->id, 'fight_id' => $this->fight->id, 'side' => 'wala', 'amount' => 50, 'status' => 'matched']);

        $this->fight->update(['status' => 'closed']);
        app(BettingService::class)->settleBets($this->fight, 'meron');

        // Both already at the VIP tier (0.5%) before this bet: 0.5% of 100
        // and 0.5% of 50, win or lose.
        $this->assertEquals(0.50, (float) VipRebateLog::where('player_id', $meronPlayer->id)->value('amount'));
        $this->assertEquals(0.25, (float) VipRebateLog::where('player_id', $walaPlayer->id)->value('amount'));
    }

    public function test_a_draw_bet_earns_no_rebate(): void
    {
        $player = $this->player();
        $player->wallet->update(['lifetime_valid_bets' => 5000]);

        Bet::create(['user_id' => $player->id, 'fight_id' => $this->fight->id, 'side' => 'draw', 'amount' => 50, 'status' => 'matched']);

        $this->fight->update(['status' => 'closed']);
        app(BettingService::class)->settleBets($this->fight, 'draw');

        $this->assertSame(0, VipRebateLog::count());
    }

    public function test_a_void_round_refund_earns_no_rebate(): void
    {
        $this->game->update(['plasada_mode' => 'total_pool', 'plasada' => 60]);

        $meronPlayer = $this->player();
        $walaPlayer = $this->player();

        Bet::create(['user_id' => $meronPlayer->id, 'fight_id' => $this->fight->id, 'side' => 'meron', 'amount' => 100, 'status' => 'matched']);
        Bet::create(['user_id' => $walaPlayer->id, 'fight_id' => $this->fight->id, 'side' => 'wala', 'amount' => 5, 'status' => 'matched']);

        $this->fight->update(['status' => 'closed']);
        app(BettingService::class)->settleBets($this->fight, 'meron');

        $this->fight->refresh();
        $this->assertSame('cancelled', $this->fight->status);
        $this->assertSame(0, VipRebateLog::count());
    }

    public function test_for_valid_bets_returns_null_below_the_lowest_tier(): void
    {
        $this->assertNull(VipTier::forValidBets(4999.99));
    }

    public function test_for_valid_bets_returns_the_highest_qualifying_tier(): void
    {
        $tier = VipTier::forValidBets(1500000);

        $this->assertSame('VIP 1', $tier->name);
    }

    public function test_for_valid_bets_still_resolves_the_top_tier_past_its_max(): void
    {
        VipTier::create(['name' => 'SUPER VIP', 'min_valid_bets' => 20000000, 'max_valid_bets' => 50000000, 'rebate_percent' => 1.0]);

        $tier = VipTier::forValidBets(999999999);

        $this->assertSame('SUPER VIP', $tier->name);
    }
}
