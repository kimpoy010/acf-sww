<?php

namespace Tests\Feature;

use App\Models\Cockpit;
use App\Models\Event;
use App\Models\Fight;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeclaratorEventVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'declarator']);
    }

    private function declarator(): User
    {
        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');

        return $declarator;
    }

    private function liveEvent(bool $videoEnabled = true): Event
    {
        $game = Game::create([
            'game_name' => 'pool-sabong', 'display_name' => 'Pool Sabong', 'plasada' => 5.00,
            'plasada_mode' => 'total_pool', 'draw_multiplier' => 8.00, 'max_draw_bet' => 100.00,
            'min_payout_threshold' => 130.00, 'video_enabled' => $videoEnabled,
        ]);

        return Event::create(['game_id' => $game->id, 'name' => 'Test Card', 'status' => 'live', 'draw_enabled' => true]);
    }

    public function test_the_active_fights_own_cockpit_stream_shows_on_the_event_page(): void
    {
        $declarator = $this->declarator();
        $event = $this->liveEvent();
        $cockpit = Cockpit::create(['name' => 'Ring 1', 'stream_url' => 'https://stream.example/ring1']);
        Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'open', 'draw_enabled' => true, 'cockpit_id' => $cockpit->id]);

        $response = $this->actingAs($declarator)->get(route('declarator.events.show', $event));

        $response->assertOk();
        $response->assertSee('id="live-stream"', false);
        $response->assertSee('https://stream.example/ring1', false);
        $response->assertDontSee('live-stream" class="aspect-video rounded-xl overflow-hidden border border-slate-800 bg-black mb-6" hidden', false);
    }

    public function test_no_video_container_when_the_game_has_video_disabled(): void
    {
        $declarator = $this->declarator();
        $event = $this->liveEvent(videoEnabled: false);
        $cockpit = Cockpit::create(['name' => 'Ring 1', 'stream_url' => 'https://stream.example/ring1']);
        Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'open', 'draw_enabled' => true, 'cockpit_id' => $cockpit->id]);

        $response = $this->actingAs($declarator)->get(route('declarator.events.show', $event));

        $response->assertOk();
        $response->assertDontSee('id="live-stream"', false);
    }

    public function test_falls_back_to_the_events_primary_cockpit_when_the_active_fight_has_none(): void
    {
        $declarator = $this->declarator();
        $event = $this->liveEvent();
        Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'pending', 'draw_enabled' => true]);

        $preset = \App\Models\CockpitPreset::create(['name' => 'Main Arena']);
        $cockpit = Cockpit::create(['name' => 'Ring 1', 'stream_url' => 'https://stream.example/primary']);
        $preset->cockpits()->attach($cockpit);
        $event->update(['cockpit_preset_id' => $preset->id]);

        $response = $this->actingAs($declarator)->get(route('declarator.events.show', $event));

        $response->assertOk();
        $response->assertSee('https://stream.example/primary', false);
    }

    public function test_the_fights_panel_refresh_includes_the_current_stream_url(): void
    {
        $declarator = $this->declarator();
        $event = $this->liveEvent();
        $cockpit = Cockpit::create(['name' => 'Ring 1', 'stream_url' => 'https://stream.example/ring1']);
        Fight::create(['event_id' => $event->id, 'fight_number' => 1, 'status' => 'open', 'draw_enabled' => true, 'cockpit_id' => $cockpit->id]);

        $response = $this->actingAs($declarator)->getJson(route('declarator.events.fights-panel', $event));

        $response->assertOk();
        $response->assertJson(['main_stream_url' => 'https://stream.example/ring1']);
    }
}
