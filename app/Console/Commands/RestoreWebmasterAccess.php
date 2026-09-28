<?php

namespace App\Console\Commands;

use App\Http\Controllers\Superadmin\RoleController;
use App\Support\AuditLogger;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Recovery command for the RBAC screen's own footgun: the webmaster
 * role (or any role) can be saved there with every checkbox unchecked,
 * silently stripping it of every admin permission with no undo in the
 * UI itself. This restores the full admin permission set — the same
 * one the webmaster/superadmin roles are seeded with by the
 * 2026_09_28_000002 migration — without needing tinker.
 */
class RestoreWebmasterAccess extends Command
{
    protected $signature = 'webmaster:restore-access {role=webmaster : Which role to restore full admin access to}';

    protected $description = 'Grant a role every admin permission (default: webmaster) — use after an accidental permission wipe from Roles & Permissions';

    public function handle(): int
    {
        $roleName = $this->argument('role');

        foreach (RoleController::permissionNames() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $before = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->syncPermissions(RoleController::permissionNames());

        $after = $role->permissions()->get()->pluck('name')->sort()->values()->all();

        AuditLogger::log(
            action: 'role.permissions_updated',
            description: "Permissions restored for role {$roleName} via webmaster:restore-access.",
            target: $role,
            changes: ['permissions' => ['old' => $before, 'new' => $after]],
        );

        $this->info("Granted every admin permission to the '{$roleName}' role.");

        return self::SUCCESS;
    }
}
