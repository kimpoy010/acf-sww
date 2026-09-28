<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the permission set the app's `permission:...` route middleware
 * checks, and the initial role -> permission grants that reproduce
 * today's access exactly: superadmin and webmaster get every admin
 * permission (webmaster is meant to be scoped down later from the
 * Roles & Permissions screen — see Superadmin\RoleController — not by
 * editing this migration), and declarator keeps just the one permission
 * its shared event-management routes need.
 *
 * A migration (not just a seeder) so these rows — and the grants —
 * exist on any environment that only ever runs `migrate`, same
 * reasoning as the combined-sabong game row and the webmaster role
 * itself. Roles are `firstOrCreate`d rather than assumed to exist,
 * since this can run before RolesAndUsersSeeder ever has.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'manage-roles',
        'manage-agents',
        'manage-staff',
        'manage-games',
        'manage-events',
        'manage-cockpits',
        'manage-cockpit-presets',
        'manage-odds-tiers',
        'manage-rfid-terminals',
        'manage-wallets',
        'manage-settings',
        'manage-approval-pin',
        'view-audit-log',
        'view-reports',
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $webmaster = Role::firstOrCreate(['name' => 'webmaster', 'guard_name' => 'web']);
        $declarator = Role::firstOrCreate(['name' => 'declarator', 'guard_name' => 'web']);

        $superadmin->givePermissionTo(self::PERMISSIONS);
        $webmaster->givePermissionTo(self::PERMISSIONS);
        $declarator->givePermissionTo('manage-events');
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->delete();
    }
};
