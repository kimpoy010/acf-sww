<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperadminStaffTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'superadmin']);
        Role::firstOrCreate(['name' => 'teller']);
        Role::firstOrCreate(['name' => 'declarator']);

        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        Wallet::create(['user_id' => $admin->id]);

        return $admin;
    }

    /**
     * The app no longer supports teller accounts — this page's own
     * validation (Superadmin\StaffController::ROLES is just
     * ['declarator'] now) rejects it as a creatable role, same as it
     * already did for 'superadmin' below.
     */
    public function test_teller_is_not_a_creatable_role(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('superadmin.staff.store'), [
            'role' => 'teller',
            'name' => 'New Teller',
            'username' => 'new_teller',
            'email' => 'new-teller@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertNull(User::where('username', 'new_teller')->first());
    }

    public function test_a_superadmin_can_create_a_declarator_account(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('superadmin.staff.store'), [
            'role' => 'declarator',
            'name' => 'New Declarator',
            'username' => 'new_declarator',
            'email' => 'new-declarator@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('superadmin.staff.index'));

        $declarator = User::where('username', 'new_declarator')->firstOrFail();
        $this->assertTrue($declarator->hasRole('declarator'));
        $this->assertFalse($declarator->hasRole('teller'));
    }

    public function test_the_created_account_can_log_in_and_reach_its_role_area(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('superadmin.staff.store'), [
            'role' => 'declarator',
            'name' => 'Login Declarator',
            'username' => 'login_declarator',
            'email' => 'login-declarator@example.com',
            'password' => 'password123',
        ]);

        $declarator = User::where('username', 'login_declarator')->firstOrFail();

        $this->actingAs($declarator)->get(route('declarator.events.index'))->assertOk();
    }

    public function test_an_invalid_role_is_rejected(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('superadmin.staff.store'), [
            'role' => 'superadmin',
            'name' => 'Sneaky',
            'username' => 'sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertNull(User::where('username', 'sneaky')->first());
    }

    public function test_a_duplicate_username_is_rejected(): void
    {
        $admin = $this->admin();
        $existing = User::factory()->create(['username' => 'taken_name']);
        $existing->assignRole('declarator');
        Wallet::create(['user_id' => $existing->id]);

        $response = $this->actingAs($admin)->post(route('superadmin.staff.store'), [
            'role' => 'declarator',
            'name' => 'Duplicate',
            'username' => 'taken_name',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    /**
     * A teller row can still exist (e.g. one the teller:delete-accounts
     * command hasn't been run against yet) — it must not show up here
     * regardless, same as a player never has.
     */
    public function test_the_index_lists_only_declarators(): void
    {
        $admin = $this->admin();
        Role::firstOrCreate(['name' => 'player']);

        $teller = User::factory()->create(['username' => 'the_teller']);
        $teller->assignRole('teller');
        Wallet::create(['user_id' => $teller->id]);

        $declarator = User::factory()->create(['username' => 'the_declarator']);
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $player = User::factory()->create(['username' => 'the_player']);
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id]);

        $response = $this->actingAs($admin)->get(route('superadmin.staff.index'));

        $response->assertOk();
        $response->assertDontSee('the_teller');
        $response->assertSee('the_declarator');
        $response->assertDontSee('the_player');
    }

    public function test_a_non_superadmin_cannot_reach_the_staff_pages(): void
    {
        Role::firstOrCreate(['name' => 'declarator']);
        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $this->actingAs($declarator)->get(route('superadmin.staff.index'))->assertForbidden();
        $this->actingAs($declarator)->get(route('superadmin.staff.create'))->assertForbidden();
        $this->actingAs($declarator)->post(route('superadmin.staff.store'), [
            'role' => 'declarator', 'name' => 'X', 'username' => 'x', 'email' => 'x@example.com', 'password' => 'password123',
        ])->assertForbidden();
        $this->actingAs($declarator)->get(route('superadmin.staff.edit', $declarator))->assertForbidden();
        $this->actingAs($declarator)->put(route('superadmin.staff.update', $declarator), [])->assertForbidden();
        $this->actingAs($declarator)->post(route('superadmin.staff.toggle-status', $declarator))->assertForbidden();
    }

    public function test_a_superadmin_can_edit_a_staff_members_details(): void
    {
        $admin = $this->admin();
        $declarator = User::factory()->create(['name' => 'Old Name', 'username' => 'old_username', 'email' => 'old@example.com']);
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $response = $this->actingAs($admin)->put(route('superadmin.staff.update', $declarator), [
            'role' => 'declarator',
            'name' => 'New Name',
            'username' => 'new_username',
            'email' => 'new@example.com',
            'password' => '',
        ]);

        $response->assertRedirect(route('superadmin.staff.index'));
        $declarator->refresh();
        $this->assertSame('New Name', $declarator->name);
        $this->assertSame('new_username', $declarator->username);
        $this->assertSame('new@example.com', $declarator->email);
    }

    public function test_a_blank_password_on_edit_keeps_the_existing_password(): void
    {
        $admin = $this->admin();
        $declarator = User::factory()->create(['password' => bcrypt('original-password')]);
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);
        $originalHash = $declarator->password;

        $this->actingAs($admin)->put(route('superadmin.staff.update', $declarator), [
            'role' => 'declarator',
            'name' => $declarator->name,
            'username' => $declarator->username,
            'email' => $declarator->email,
            'password' => '',
        ]);

        $this->assertSame($originalHash, $declarator->fresh()->password);
    }

    public function test_a_provided_password_on_edit_changes_it(): void
    {
        $admin = $this->admin();
        $declarator = User::factory()->create(['password' => bcrypt('original-password')]);
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $this->actingAs($admin)->put(route('superadmin.staff.update', $declarator), [
            'role' => 'declarator',
            'name' => $declarator->name,
            'username' => $declarator->username,
            'email' => $declarator->email,
            'password' => 'brand-new-password',
        ]);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-password', $declarator->fresh()->password));
    }

    public function test_a_superadmin_can_deactivate_and_reactivate_a_staff_member(): void
    {
        $admin = $this->admin();
        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $this->actingAs($admin)->post(route('superadmin.staff.toggle-status', $declarator))->assertRedirect();
        $this->assertSame('inactive', $declarator->fresh()->status);

        $this->actingAs($admin)->post(route('superadmin.staff.toggle-status', $declarator))->assertRedirect();
        $this->assertSame('active', $declarator->fresh()->status);
    }

    public function test_a_deactivated_staff_member_is_signed_out_on_their_next_request(): void
    {
        $admin = $this->admin();
        $declarator = User::factory()->create();
        $declarator->assignRole('declarator');
        Wallet::create(['user_id' => $declarator->id]);

        $this->actingAs($admin)->post(route('superadmin.staff.toggle-status', $declarator));

        // actingAs() authenticates using this exact in-memory model, so it
        // must be refreshed first — otherwise the guard would carry the
        // stale (still-active) status the variable was created with.
        $response = $this->actingAs($declarator->refresh())->get(route('declarator.events.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_editing_a_non_staff_user_through_this_controller_is_rejected(): void
    {
        $admin = $this->admin();
        Role::firstOrCreate(['name' => 'player']);
        $player = User::factory()->create();
        $player->assignRole('player');
        Wallet::create(['user_id' => $player->id]);

        $this->actingAs($admin)->get(route('superadmin.staff.edit', $player))->assertStatus(422);
        $this->actingAs($admin)->put(route('superadmin.staff.update', $player), [
            'role' => 'declarator', 'name' => 'X', 'username' => 'x2', 'email' => 'x2@example.com', 'password' => '',
        ])->assertStatus(422);
        $this->actingAs($admin)->post(route('superadmin.staff.toggle-status', $player))->assertStatus(422);
    }

    /**
     * A leftover teller row (not yet purged by teller:delete-accounts)
     * is no longer manageable through this page either, same as any
     * other non-declarator user.
     */
    public function test_editing_an_existing_teller_through_this_controller_is_rejected(): void
    {
        $admin = $this->admin();
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        Wallet::create(['user_id' => $teller->id]);

        $this->actingAs($admin)->get(route('superadmin.staff.edit', $teller))->assertStatus(422);
        $this->actingAs($admin)->put(route('superadmin.staff.update', $teller), [
            'role' => 'declarator', 'name' => 'X', 'username' => 'x3', 'email' => 'x3@example.com', 'password' => '',
        ])->assertStatus(422);
        $this->actingAs($admin)->post(route('superadmin.staff.toggle-status', $teller))->assertStatus(422);
    }
}
