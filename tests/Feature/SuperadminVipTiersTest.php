<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VipTier;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperadminVipTiersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'superadmin']);

        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        $admin->givePermissionTo('manage-vip-tiers');
        Wallet::create(['user_id' => $admin->id]);

        return $admin;
    }

    public function test_an_authorized_admin_can_view_the_vip_tiers_page(): void
    {
        $admin = $this->admin();
        VipTier::create(['name' => 'VIP', 'min_valid_bets' => 5000, 'rebate_percent' => 0.5]);

        $response = $this->actingAs($admin)->get(route('superadmin.vip-tiers.index'));

        $response->assertOk();
        $response->assertSee('VIP');
    }

    public function test_an_admin_can_create_a_vip_tier(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('superadmin.vip-tiers.store'), [
            'name' => 'VIP 1',
            'min_valid_bets' => 1000000,
            'max_valid_bets' => 4999999.99,
            'rebate_percent' => 0.7,
        ]);

        $response->assertRedirect(route('superadmin.vip-tiers.index'));
        $this->assertDatabaseHas('vip_tiers', ['name' => 'VIP 1', 'rebate_percent' => 0.7]);
    }

    public function test_an_admin_can_update_a_vip_tier(): void
    {
        $admin = $this->admin();
        $tier = VipTier::create(['name' => 'VIP', 'min_valid_bets' => 5000, 'rebate_percent' => 0.5]);

        $response = $this->actingAs($admin)->put(route('superadmin.vip-tiers.update', $tier), [
            'name' => 'VIP',
            'min_valid_bets' => 5000,
            'rebate_percent' => 0.6,
        ]);

        $response->assertRedirect(route('superadmin.vip-tiers.index'));
        $this->assertEquals(0.6, (float) $tier->fresh()->rebate_percent);
    }

    public function test_an_admin_can_delete_a_vip_tier(): void
    {
        $admin = $this->admin();
        $tier = VipTier::create(['name' => 'VIP', 'min_valid_bets' => 5000, 'rebate_percent' => 0.5]);

        $response = $this->actingAs($admin)->delete(route('superadmin.vip-tiers.destroy', $tier));

        $response->assertRedirect(route('superadmin.vip-tiers.index'));
        $this->assertDatabaseMissing('vip_tiers', ['id' => $tier->id]);
    }

    public function test_a_user_without_the_permission_cannot_view_the_page(): void
    {
        Role::firstOrCreate(['name' => 'declarator']);
        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $response = $this->actingAs($declarator)->get(route('superadmin.vip-tiers.index'));

        $response->assertForbidden();
    }
}
