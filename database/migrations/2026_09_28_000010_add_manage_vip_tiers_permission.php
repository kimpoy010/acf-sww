<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Adds the manage-vip-tiers permission for the new VIP rebate tiers admin
 * screen (Superadmin\VipTierController) — same grant pattern as every
 * other permission added in 2026_09_28_000002_create_rbac_permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'manage-vip-tiers', 'guard_name' => 'web']);

        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $webmaster = Role::firstOrCreate(['name' => 'webmaster', 'guard_name' => 'web']);

        $superadmin->givePermissionTo('manage-vip-tiers');
        $webmaster->givePermissionTo('manage-vip-tiers');
    }

    public function down(): void
    {
        Permission::where('name', 'manage-vip-tiers')->where('guard_name', 'web')->delete();
    }
};
