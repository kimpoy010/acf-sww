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
use App\Services\CombinedBettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * VIP rebate crediting for CombinedSabong's pool subset — a separate
 * settlement code path from plain BettingService (see VipRebateServiceTest
 * for that one), mirroring exactly where CombinedBettingService already
 * calls CommissionService for the same subset.
 */
class CombinedBettingVipRebateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pool_subset_settlement_credits_vip_rebate_on_both_sides(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        VipTier::query()->delete();
        VipTier::create(['name' => 'VIP', 'min_valid_bets' => 5000, 'max_valid_bets' => 999999.99, 'rebate_percent' => 0.5]);

        $game = Game::create([
            'game_name' => 'combined-sabong-test',
            'game_type' => 'combined',
            'display_name' => 'Combined Sabong Test',
            'plasada' => 5.00,
            'plasada_mode' => 'total_pool',
            'odds_plasada' => 5.00,
            'draw_multiplier' => 8.00,
            'max_draw_bet' => 100.00,
            'min_payout_threshold' => 130.00,
        ]);

        $event = Event::create(['game_id' => $game->id, 'name' => 'Test Card', 'status' => 'live', 'draw_enabled' => true]);
        $fight = Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'open', 'draw_enabled' => true]);

        $meronPlayer = User::factory()->create();
        $meronPlayer->assignRole('player');
        Wallet::create(['user_id' => $meronPlayer->id, 'main_balance' => 1000, 'lifetime_valid_bets' => 5000]);

        $walaPlayer = User::factory()->create();
        $walaPlayer->assignRole('player');
        Wallet::create(['user_id' => $walaPlayer->id, 'main_balance' => 1000, 'lifetime_valid_bets' => 5000]);

        Bet::create(['user_id' => $meronPlayer->id, 'fight_id' => $fight->id, 'side' => 'meron', 'amount' => 100, 'matched_amount' => 100, 'unmatched_amount' => 0, 'status' => 'matched']);
        Bet::create(['user_id' => $walaPlayer->id, 'fight_id' => $fight->id, 'side' => 'wala', 'amount' => 50, 'matched_amount' => 50, 'unmatched_amount' => 0, 'status' => 'matched']);

        $fight->update(['status' => 'closed']);
        app(CombinedBettingService::class)->settleBets($fight, 'meron');

        $this->assertEquals(0.50, (float) VipRebateLog::where('player_id', $meronPlayer->id)->value('amount'));
        $this->assertEquals(0.25, (float) VipRebateLog::where('player_id', $walaPlayer->id)->value('amount'));
    }
}
