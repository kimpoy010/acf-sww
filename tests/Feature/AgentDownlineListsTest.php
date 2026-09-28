<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentDownlineListsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => 'player']);
    }

    private function agent(): User
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent');
        Wallet::create(['user_id' => $agent->id]);

        return $agent;
    }

    public function test_the_players_list_shows_every_downline_player_sorted_by_balance(): void
    {
        $agent = $this->agent();

        User::factory()->count(12)->create(['agent_id' => $agent->id])->each(function (User $player, int $i) {
            $player->assignRole('player');
            Wallet::create(['user_id' => $player->id, 'main_balance' => ($i + 1) * 10]);
        });

        $response = $this->actingAs($agent)->get(route('agent.downline.players'));

        $response->assertOk();
        $response->assertViewHas('downlinePlayers', function ($downlinePlayers) {
            return $downlinePlayers->total() === 12
                && $downlinePlayers->count() === 12
                && (float) $downlinePlayers->first()->wallet->main_balance === 120.0
                && (float) $downlinePlayers->last()->wallet->main_balance === 10.0;
        });
    }

    public function test_the_agents_list_shows_every_downline_agent_sorted_by_balance(): void
    {
        $agent = $this->agent();

        User::factory()->count(3)->create(['agent_id' => $agent->id])->each(function (User $subAgent, int $i) {
            $subAgent->assignRole('agent');
            Wallet::create(['user_id' => $subAgent->id, 'main_balance' => ($i + 1) * 50]);
        });

        $response = $this->actingAs($agent)->get(route('agent.downline.agents'));

        $response->assertOk();
        $response->assertViewHas('downlineAgents', function ($downlineAgents) {
            return $downlineAgents->total() === 3
                && (float) $downlineAgents->first()->wallet->main_balance === 150.0;
        });
    }

    public function test_an_agent_cannot_see_another_agents_downline_in_the_lists(): void
    {
        $agentA = $this->agent();
        $agentB = $this->agent();

        $player = User::factory()->create(['agent_id' => $agentB->id]);
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id]);

        $response = $this->actingAs($agentA)->get(route('agent.downline.players'));

        $response->assertOk();
        $response->assertDontSee($player->displayName());
    }
}
