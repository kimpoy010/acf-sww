<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'superadmin'): User
    {
        // 'player' is what Superadmin\WalletController::index() itself
        // scopes by (User::role('player')), unconditionally — not
        // something this RBAC test is actually about, just a role that
        // must exist for that controller not to blow up.
        Role::firstOrCreate(['name' => 'player']);
        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => $role]);

        $admin = User::factory()->create();
        $admin->assignRole($role);
        Wallet::create(['user_id' => $admin->id]);

        return $admin;
    }

    public function test_superadmin_migration_grants_every_admin_permission_by_default(): void
    {
        $admin = $this->admin('superadmin');

        $this->assertTrue($admin->can('manage-wallets'));
        $this->assertTrue($admin->can('manage-agents'));
        $this->assertTrue($admin->can('manage-roles'));
    }

    public function test_webmaster_gets_the_same_default_permissions_as_superadmin(): void
    {
        $webmaster = $this->admin('webmaster');

        $this->assertTrue($webmaster->can('manage-wallets'));
        $this->assertTrue($webmaster->can('manage-roles'));
    }

    public function test_revoking_a_permission_from_a_role_blocks_the_matching_route_immediately(): void
    {
        $webmaster = $this->admin('webmaster');

        // Sanity check: reachable before the permission is revoked.
        $this->actingAs($webmaster)->get(route('superadmin.wallets.index'))->assertOk();

        Role::findByName('webmaster')->revokePermissionTo('manage-wallets');

        $this->actingAs($webmaster)->get(route('superadmin.wallets.index'))->assertForbidden();

        // A section it still holds stays reachable — revoking one
        // permission doesn't touch the others.
        $this->actingAs($webmaster)->get(route('superadmin.agents.index'))->assertOk();
    }

    public function test_a_role_with_no_admin_permissions_is_forbidden_from_every_superadmin_section(): void
    {
        Role::firstOrCreate(['name' => 'teller']);
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        Wallet::create(['user_id' => $teller->id]);

        // A teller isn't even in role:superadmin|webmaster, so this is
        // blocked at the outer role gate before permissions come into it.
        $this->actingAs($teller)->get(route('superadmin.wallets.index'))->assertForbidden();
    }

    public function test_superadmin_can_view_and_update_the_roles_and_permissions_screen(): void
    {
        $admin = $this->admin('superadmin');
        Role::firstOrCreate(['name' => 'webmaster'])->givePermissionTo('manage-wallets', 'manage-agents');

        $this->actingAs($admin)->get(route('superadmin.roles.index'))->assertOk();

        $webmasterRole = Role::findByName('webmaster');

        $response = $this->actingAs($admin)->put(route('superadmin.roles.update', $webmasterRole), [
            'permissions' => ['manage-agents'],
        ]);

        $response->assertRedirect();
        $webmasterRole->refresh();
        $this->assertTrue($webmasterRole->hasPermissionTo('manage-agents'));
        $this->assertFalse($webmasterRole->hasPermissionTo('manage-wallets'));
    }

    public function test_the_superadmin_role_cannot_be_stripped_of_manage_roles_permission(): void
    {
        $admin = $this->admin('superadmin');
        $superadminRole = Role::findByName('superadmin');

        $response = $this->actingAs($admin)->put(route('superadmin.roles.update', $superadminRole), [
            'permissions' => ['manage-wallets'],
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($superadminRole->fresh()->hasPermissionTo('manage-roles'));
    }

    public function test_admin_pin_approver_lookup_follows_the_manage_approval_pin_permission(): void
    {
        $webmaster = $this->admin('webmaster');
        $webmaster->update(['pin' => '4321']);

        $service = app(\App\Services\AdminPinService::class);
        $this->assertNotNull($service->findApprover('4321'));

        Role::findByName('webmaster')->revokePermissionTo('manage-approval-pin');
        $webmaster->refresh()->load('roles', 'permissions');

        $this->assertNull($service->findApprover('4321'));
    }

    public function test_webmaster_restore_access_command_recovers_from_a_full_permission_wipe(): void
    {
        $this->admin('webmaster');
        $webmasterRole = Role::findByName('webmaster');
        $webmasterRole->syncPermissions([]);
        $this->assertCount(0, $webmasterRole->fresh()->permissions);

        $this->artisan('webmaster:restore-access')->assertSuccessful();

        $restored = $webmasterRole->fresh()->permissions->pluck('name')->sort()->values()->all();
        $expected = collect(\App\Http\Controllers\Superadmin\RoleController::permissionNames())->sort()->values()->all();
        $this->assertEquals($expected, $restored);
    }
}
