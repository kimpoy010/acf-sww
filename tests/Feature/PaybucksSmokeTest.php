<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaybucksSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_flow_end_to_end(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);

        Http::fake([
            '*/payments/deposits' => Http::response([
                'transactionId' => 'ext-123',
                'merchantOrderNo' => 'whatever',
                'amount' => 500,
                'paymentUrl' => 'https://pay.example/x',
                'qrPayload' => 'qr-payload-data',
                'qrImageUrl' => 'https://pay.example/qr.png',
                'transTime' => now()->toISOString(),
            ], 200),
            '*/payments/orders/*' => Http::response([
                'status' => 'SUCCESS',
                'merchantOrderNo' => 'whatever',
                'kind' => 'DEPOSIT',
                'amount' => 500,
            ], 200),
        ]);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 0]);

        $resp = $this->actingAs($player)->post(route('play.cash.deposit'), [
            'channel' => 'gcash',
            'amount' => 500,
            'account_number' => '09171234567',
        ]);

        $resp->assertRedirect();
        $tx = \App\Models\CashTransaction::where('user_id', $player->id)->latest()->first();
        $this->assertNotNull($tx);
        $this->assertEquals('paybucks', $tx->provider);
        $this->assertEquals('pending', $tx->status);
        $this->assertEquals('https://pay.example/qr.png', $tx->qr_image_url);

        $show = $this->actingAs($player)->get(route('play.cash.show', $tx));
        $show->assertOk();
        $show->assertSee('qr.png');

        // Simulate Paybucks' callback firing.
        $cb = $this->postJson(route('api.paybucks.callback.deposit'), [
            'merchantOrderNo' => $tx->code,
        ]);
        $cb->assertOk();
        $cb->assertJson(['status' => '1', 'error_msg' => '']);

        $tx->refresh();
        $this->assertEquals('completed', $tx->status);
        $this->assertEquals(500, $player->wallet->fresh()->main_balance);
    }

    public function test_agent_gets_same_cash_flow(): void
    {
        Role::firstOrCreate(['name' => 'agent']);
        config(['services.paybucks.api_key' => 'test-key']);

        Http::fake([
            '*/payments/deposits' => Http::response([
                'transactionId' => 'ext-999',
                'merchantOrderNo' => 'whatever',
                'amount' => 300,
                'paymentUrl' => 'https://pay.example/y',
            ], 200),
            '*/payments/orders/*' => Http::response([
                'status' => 'PENDING',
                'merchantOrderNo' => 'whatever',
                'kind' => 'DEPOSIT',
                'amount' => 300,
            ], 200),
        ]);

        $agent = User::factory()->create();
        $agent->assignRole('agent');
        $agent->wallet()->create(['main_balance' => 0]);

        $resp = $this->actingAs($agent)->post(route('agent.cash.deposit'), [
            'channel' => 'maya',
            'amount' => 300,
        ]);

        $resp->assertRedirect();
        $tx = \App\Models\CashTransaction::where('user_id', $agent->id)->latest()->first();
        $this->assertNotNull($tx);

        $show = $this->actingAs($agent)->get(route('agent.cash.show', $tx));
        $show->assertOk();
    }
}
