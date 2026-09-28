<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperadminPayoutSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'superadmin'])->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage-settings'])
        );

        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        Wallet::create(['user_id' => $admin->id]);

        return $admin;
    }

    public function test_it_defaults_to_no_withdrawal_fee(): void
    {
        $this->assertEquals(0.0, \App\Services\CashTransactionService::withdrawalFee());
    }

    public function test_a_superadmin_can_set_the_withdrawal_fee(): void
    {
        $admin = $this->admin();

        $resp = $this->actingAs($admin)->put(route('superadmin.settings.update'), [
            'withdrawal_fee' => '15.50',
        ]);

        $resp->assertRedirect(route('superadmin.settings.edit'));
        $this->assertEquals('15.50', Setting::get('withdrawal_fee'));
        $this->assertEquals(15.50, \App\Services\CashTransactionService::withdrawalFee());
    }

    public function test_a_negative_withdrawal_fee_is_rejected(): void
    {
        $admin = $this->admin();

        $resp = $this->actingAs($admin)->put(route('superadmin.settings.update'), [
            'withdrawal_fee' => '-5',
        ]);

        $resp->assertSessionHasErrors('withdrawal_fee');
        $this->assertEquals(0.0, \App\Services\CashTransactionService::withdrawalFee());
    }
}
