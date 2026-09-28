<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentDashboardDownlinePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_shows_only_the_top_ten_downline_players_by_balance(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        Role::firstOrCreate(['name' => 'agent']);

        $agent = User::factory()->create();
        $agent->assignRole('agent');
        Wallet::create(['user_id' => $agent->id]);

        // One more than the dashboard's cap, with distinct balances so
        // highest-to-lowest order is unambiguous.
        User::factory()->count(11)->create(['agent_id' => $agent->id])->each(function (User $player, int $i) {
            $player->assignRole('player');
            Wallet::create(['user_id' => $player->id, 'main_balance' => ($i + 1) * 100]);
        });

        $response = $this->actingAs($agent)->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertViewHas('downlinePlayersCount', 11);
        $response->assertViewHas('downlinePlayers', function ($downlinePlayers) {
            return $downlinePlayers->count() === 10
                && (float) $downlinePlayers->first()->wallet->main_balance === 1100.0
                && (float) $downlinePlayers->last()->wallet->main_balance === 200.0;
        });
        $response->assertSee('Downline players (11)');
        $response->assertSee(route('agent.downline.players'), false);
    }

    public function test_the_dashboard_shows_only_the_top_ten_downline_agents_by_balance(): void
    {
        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => 'player']);

        $agent = User::factory()->create();
        $agent->assignRole('agent');
        Wallet::create(['user_id' => $agent->id]);

        User::factory()->count(11)->create(['agent_id' => $agent->id])->each(function (User $subAgent, int $i) {
            $subAgent->assignRole('agent');
            Wallet::create(['user_id' => $subAgent->id, 'main_balance' => ($i + 1) * 100]);
        });

        $response = $this->actingAs($agent)->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertViewHas('downlineAgentsCount', 11);
        $response->assertViewHas('downlineAgents', fn ($downlineAgents) => $downlineAgents->count() === 10);
        $response->assertSee('Downline sub-agents (11)');
        $response->assertSee(route('agent.downline.agents'), false);
    }
}
