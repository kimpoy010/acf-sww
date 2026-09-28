<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WalletPinControllerTest extends TestCase
{
    use RefreshDatabase;

    private function player(): User
    {
        Role::firstOrCreate(['name' => 'player']);

        $player = User::factory()->create();
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id, 'main_balance' => 1000]);

        return $player;
    }

    public function test_a_player_can_set_a_wallet_pin(): void
    {
        $player = $this->player();
        $this->assertFalse($player->hasWalletPin());

        $resp = $this->actingAs($player)->post(route('play.wallet-pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);

        $resp->assertRedirect(route('play.profile'));
        $resp->assertSessionHas('success');
        $player->refresh();
        $this->assertTrue($player->hasWalletPin());
        $this->assertTrue($player->checkWalletPin('1234'));
        $this->assertFalse($player->checkWalletPin('4321'));
    }

    public function test_a_player_can_change_an_existing_pin_without_entering_the_old_one(): void
    {
        // Same reasoning as AccountController::updatePassword() — the
        // session is already proof of who's asking.
        $player = $this->player();
        $player->update(['pin' => Hash::make('1111')]);

        $resp = $this->actingAs($player)->post(route('play.wallet-pin.update'), [
            'pin' => '2222',
            'pin_confirmation' => '2222',
        ]);

        $resp->assertRedirect(route('play.profile'));
        $player->refresh();
        $this->assertTrue($player->checkWalletPin('2222'));
        $this->assertFalse($player->checkWalletPin('1111'));
    }

    public function test_the_pin_must_be_exactly_four_digits(): void
    {
        $player = $this->player();

        $resp = $this->actingAs($player)->post(route('play.wallet-pin.update'), [
            'pin' => '12345',
            'pin_confirmation' => '12345',
        ]);

        $resp->assertSessionHasErrors('pin');
        $this->assertFalse($player->fresh()->hasWalletPin());
    }

    public function test_the_pin_confirmation_must_match(): void
    {
        $player = $this->player();

        $resp = $this->actingAs($player)->post(route('play.wallet-pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '5678',
        ]);

        $resp->assertSessionHasErrors('pin');
        $this->assertFalse($player->fresh()->hasWalletPin());
    }

    public function test_the_stored_pin_is_hashed_not_plaintext(): void
    {
        $player = $this->player();

        $this->actingAs($player)->post(route('play.wallet-pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);

        $this->assertNotEquals('1234', $player->fresh()->getRawOriginal('pin'));
    }
}
