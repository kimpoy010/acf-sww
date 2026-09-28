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

        // Real shape confirmed from a live deposit (see
        // paybucks.deposit_response_missing_payment_fields in the logs
        // that caught this): every endpoint wraps its actual payload in
        // {success, statusCode, data: {...}}, not flat fields as the
        // vendor doc's examples show.
        Http::fake([
            '*/payments/deposits' => Http::response([
                'success' => true,
                'statusCode' => 201,
                'data' => [
                    'transactionId' => 'ext-123',
                    'merchantOrderNo' => 'whatever',
                    'amount' => 500,
                    'paymentUrl' => 'https://pay.example/x',
                    'qrPayload' => 'qr-payload-data',
                    'qrImageUrl' => 'https://pay.example/qr.png',
                    'transTime' => now()->toISOString(),
                ],
            ], 201),
            '*/payments/orders/*' => Http::response([
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'status' => 'SUCCESS',
                    'merchantOrderNo' => 'whatever',
                    'kind' => 'DEPOSIT',
                    'amount' => 500,
                ],
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
                'success' => true,
                'statusCode' => 201,
                'data' => [
                    'transactionId' => 'ext-999',
                    'merchantOrderNo' => 'whatever',
                    'amount' => 300,
                    'paymentUrl' => 'https://pay.example/y',
                ],
            ], 201),
            '*/payments/orders/*' => Http::response([
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'status' => 'PENDING',
                    'merchantOrderNo' => 'whatever',
                    'kind' => 'DEPOSIT',
                    'amount' => 300,
                ],
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

    public function test_withdrawal_is_refused_without_a_saved_payment_method(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 1000]);

        $resp = $this->actingAs($player)->post(route('play.cash.withdraw'), ['channel' => 'gcash', 'amount' => 500]);

        $resp->assertRedirect(route('play.payment-methods.index'));
        $this->assertDatabaseCount('cash_transactions', 0);
        $this->assertEquals(1000, $player->wallet->fresh()->main_balance);
    }

    public function test_saving_a_payment_method_then_unlocks_a_withdrawal_to_it(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);

        Http::fake([
            '*/payments/withdrawals' => Http::response([
                'success' => true,
                'statusCode' => 201,
                'data' => [
                    'transactionId' => 'ext-w1',
                    'merchantOrderNo' => 'whatever',
                    'amount' => 1000,
                    'fee' => 5,
                ],
            ], 201),
        ]);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 1000]);

        $save = $this->actingAs($player)->post(route('play.payment-methods.update'), [
            'channel' => 'gcash',
            'account_number' => '09171234567',
        ]);
        $save->assertRedirect(route('play.payment-methods.index'));
        $this->assertEquals('09171234567', $player->fresh()->gcash_account_number);

        $resp = $this->actingAs($player)->post(route('play.cash.withdraw'), ['channel' => 'gcash', 'amount' => 400]);
        $resp->assertRedirect();

        $tx = \App\Models\CashTransaction::where('user_id', $player->id)->latest()->first();
        $this->assertNotNull($tx);
        $this->assertEquals('09171234567', $tx->account_number);
        $this->assertEquals('pending', $tx->status);
        $this->assertEquals(400, (float) $tx->amount);

        // The rest of the balance stays available, not reserved — a
        // partial withdrawal only holds back what was actually asked for.
        $this->assertEquals(600, $player->wallet->fresh()->availableBalance());
    }

    public function test_a_withdrawal_amount_over_the_available_balance_is_rejected(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 100]);

        $this->actingAs($player)->post(route('play.payment-methods.update'), [
            'channel' => 'gcash',
            'account_number' => '09171234567',
        ]);

        $resp = $this->actingAs($player)->post(route('play.cash.withdraw'), ['channel' => 'gcash', 'amount' => 500]);

        $resp->assertRedirect(route('play.cash.index'));
        $this->assertDatabaseCount('cash_transactions', 0);
        $this->assertEquals(100, $player->wallet->fresh()->main_balance);
    }

    public function test_a_platform_withdrawal_fee_is_reserved_and_charged_on_top_of_the_amount(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);
        \App\Models\Setting::set('withdrawal_fee', '10');

        Http::fake([
            '*/payments/withdrawals' => Http::response([
                'success' => true,
                'statusCode' => 201,
                'data' => ['transactionId' => 'ext-fee', 'merchantOrderNo' => 'whatever', 'amount' => 400],
            ], 201),
            '*/payments/orders/*' => Http::response([
                'success' => true,
                'statusCode' => 200,
                'data' => ['status' => 'SUCCESS', 'merchantOrderNo' => 'whatever', 'kind' => 'WITHDRAW', 'amount' => 400],
            ], 200),
        ]);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 1000]);

        $this->actingAs($player)->post(route('play.payment-methods.update'), [
            'channel' => 'gcash',
            'account_number' => '09171234567',
        ]);

        // Requesting the full 1000 would exceed available balance once the
        // 10 fee is accounted for.
        $this->actingAs($player)->post(route('play.cash.withdraw'), ['channel' => 'gcash', 'amount' => 1000])
            ->assertRedirect(route('play.cash.index'));
        $this->assertDatabaseCount('cash_transactions', 0);

        $resp = $this->actingAs($player)->post(route('play.cash.withdraw'), ['channel' => 'gcash', 'amount' => 400]);
        $resp->assertRedirect();

        $tx = \App\Models\CashTransaction::where('user_id', $player->id)->latest()->first();
        $this->assertEquals(400, (float) $tx->amount);
        $this->assertEquals(10, (float) $tx->platform_fee);
        // 400 requested + 10 fee reserved, out of 1000 — 590 still available.
        $this->assertEquals(590, $player->wallet->fresh()->availableBalance());

        $show = $this->actingAs($player)->get(route('play.cash.show', $tx));
        $show->assertOk();

        $tx->refresh();
        $this->assertEquals('completed', $tx->status);
        // 1000 - 400 - 10 fee = 590 actually charged to main_balance.
        $this->assertEquals(590, $player->wallet->fresh()->main_balance);
        $this->assertEquals(0, $player->wallet->fresh()->pending_withdrawal);
    }

    /**
     * Regression for the exact incident reported: a real deposit response
     * had paymentUrl set but qrImageUrl/qrPayload both null, wrapped in
     * {success, statusCode, data} — PaybucksClient previously returned
     * the raw envelope, so CashTransactionService read paymentUrl at the
     * top level (where it never was) and stored null for all three,
     * leaving the show page with nothing to render.
     */
    public function test_deposit_response_with_only_a_payment_url_still_works(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);

        Http::fake([
            '*/payments/deposits' => Http::response([
                'success' => true,
                'statusCode' => 201,
                'data' => [
                    'transactionId' => 'cmul9k8zq003t01oth2d5u2b8',
                    'merchantOrderNo' => 'whatever',
                    'amount' => 100,
                    'paymentUrl' => 'https://cashier.peppermint-pay.com/GCash_DepositPage.html?token=abc',
                    'qrImageUrl' => null,
                    'qrPayload' => null,
                    'transTime' => now()->toISOString(),
                ],
            ], 201),
            '*/payments/orders/*' => Http::response([
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'status' => 'PENDING',
                    'merchantOrderNo' => 'whatever',
                    'kind' => 'DEPOSIT',
                    'amount' => 100,
                ],
            ], 200),
        ]);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 0]);

        $resp = $this->actingAs($player)->post(route('play.cash.deposit'), [
            'channel' => 'gcash',
            'amount' => 100,
            'account_number' => '09171234567',
        ]);
        $resp->assertRedirect();

        $tx = \App\Models\CashTransaction::where('user_id', $player->id)->latest()->first();
        $this->assertEquals('https://cashier.peppermint-pay.com/GCash_DepositPage.html?token=abc', $tx->payment_url);
        $this->assertNull($tx->qr_image_url);

        // No qr_image_url and no qr_payload — the page must NOT draw a QR
        // out of payment_url itself (a webpage link isn't a format
        // GCash/Maya's scanner recognizes as a valid payment QR); it
        // should show the "Open payment page" link instead.
        $show = $this->actingAs($player)->get(route('play.cash.show', $tx));
        $show->assertOk();
        $show->assertSee('Open payment page');
        $show->assertDontSee("couldn&#039;t generate a way to pay", false);
    }

    public function test_the_payment_modal_opens_automatically_right_after_depositing(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);

        Http::fake([
            '*/payments/deposits' => Http::response([
                'success' => true,
                'statusCode' => 201,
                'data' => [
                    'transactionId' => 'ext-modal',
                    'merchantOrderNo' => 'whatever',
                    'amount' => 100,
                    'paymentUrl' => 'https://pay.example/z',
                ],
            ], 201),
            '*/payments/orders/*' => Http::response([
                'success' => true,
                'statusCode' => 200,
                'data' => ['status' => 'PENDING', 'merchantOrderNo' => 'whatever', 'kind' => 'DEPOSIT', 'amount' => 100],
            ], 200),
        ]);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 0]);

        // Following the redirect the way a browser actually would — the
        // flash flag only lives for this one next request.
        $show = $this->actingAs($player)->from(route('play.cash.index'))->followingRedirects()->post(route('play.cash.deposit'), [
            'channel' => 'gcash',
            'amount' => 100,
            'account_number' => '09171234567',
        ]);

        $show->assertOk();
        $show->assertSee('open();', false);

        // A plain reload of the same page must not keep reopening it.
        $tx = \App\Models\CashTransaction::where('user_id', $player->id)->latest()->first();
        $reload = $this->actingAs($player)->get(route('play.cash.show', $tx));
        $reload->assertOk();
        $reload->assertDontSee('open();', false);
    }

    public function test_a_business_level_failure_response_is_treated_as_an_error(): void
    {
        Role::firstOrCreate(['name' => 'player']);
        config(['services.paybucks.api_key' => 'test-key']);

        Http::fake([
            '*/payments/deposits' => Http::response([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Channel temporarily unavailable',
            ], 200),
        ]);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 0]);

        $resp = $this->actingAs($player)->post(route('play.cash.deposit'), [
            'channel' => 'gcash',
            'amount' => 100,
            'account_number' => '09171234567',
        ]);

        $resp->assertRedirect(route('play.cash.index'));
        $this->assertDatabaseHas('cash_transactions', ['user_id' => $player->id, 'status' => 'failed']);
    }

    public function test_maya_payment_method_requires_an_account_name(): void
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        $player->wallet()->create(['main_balance' => 0]);

        $resp = $this->actingAs($player)->post(route('play.payment-methods.update'), [
            'channel' => 'maya',
            'account_number' => '09171234567',
        ]);

        $resp->assertRedirect(route('play.payment-methods.index'));
        $this->assertNull($player->fresh()->maya_account_number);
    }
}
