<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VipTier;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlayerProfileVipStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'player']);

        VipTier::query()->delete();
        VipTier::create(['name' => 'VIP', 'min_valid_bets' => 5000, 'max_valid_bets' => 999999.99, 'rebate_percent' => 0.5]);
        VipTier::create(['name' => 'VIP 1', 'min_valid_bets' => 1000000, 'max_valid_bets' => 4999999.99, 'rebate_percent' => 0.7]);
    }

    private function player(): User
    {
        $player = User::factory()->create();
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id]);

        return $player;
    }

    public function test_a_player_below_the_lowest_tier_sees_not_yet_vip(): void
    {
        $player = $this->player();

        $response = $this->actingAs($player)->get(route('play.profile'));

        $response->assertOk();
        $response->assertSee(__('Not yet VIP'));
        $response->assertSee(__('VIP'));
    }

    public function test_a_player_at_a_tier_sees_their_tier_name_and_rebate(): void
    {
        $player = $this->player();
        $player->wallet->update(['lifetime_valid_bets' => 10000]);

        $response = $this->actingAs($player)->get(route('play.profile'));

        $response->assertOk();
        $response->assertDontSee(__('Not yet VIP'));
        $response->assertViewHas('vipTier', fn ($tier) => $tier->name === 'VIP');
        $response->assertViewHas('nextVipTier', fn ($tier) => $tier->name === 'VIP 1');
    }

    public function test_a_player_at_the_top_tier_sees_the_max_tier_message(): void
    {
        $player = $this->player();
        $player->wallet->update(['lifetime_valid_bets' => 2000000]);

        $response = $this->actingAs($player)->get(route('play.profile'));

        $response->assertOk();
        $response->assertViewHas('nextVipTier', null);
        $response->assertSee(__('You\'ve reached the highest VIP tier.'));
    }
}
