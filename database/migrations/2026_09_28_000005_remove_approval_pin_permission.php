<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

/**
 * The Approval PIN feature (Superadmin\PinController, AdminPinService,
 * the "Approval PIN" nav link/settings page) is removed — its only
 * consumer was a teller voiding a bet ticket, and teller accounts are
 * fully retired (every teller.* route 404s unconditionally). Deleting
 * the Permission row cascades to any role_has_permissions grants for it
 * (Spatie's own migration puts an onDelete('cascade') foreign key on
 * that pivot), so no role is left holding a permission nothing checks
 * anymore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::where('name', 'manage-approval-pin')->where('guard_name', 'web')->delete();
    }

    public function down(): void
    {
        Permission::firstOrCreate(['name' => 'manage-approval-pin', 'guard_name' => 'web']);
    }
};
