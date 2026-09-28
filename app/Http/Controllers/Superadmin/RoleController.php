<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * The RBAC admin screen — which of the app's admin-section permissions
 * (see the 2026_09_28_000002 migration for where this list is first
 * seeded) each role is granted. Route-level `permission:...` middleware
 * is what actually enforces these; this page just edits the grants.
 *
 * Every role in the system is listed (not just superadmin/webmaster) so
 * this stays the one place all of it is visible, even though most other
 * roles (player, agent, teller, declarator) have no reason to hold most
 * of these permissions today.
 */
class RoleController extends Controller
{
    /**
     * name => short human label, in the order the matrix's columns show
     * them. The name is what's actually checked everywhere (route
     * middleware, @can) — the label is display-only.
     *
     * @return array<string, string>
     */
    public static function permissionLabels(): array
    {
        return [
            'manage-roles' => 'Roles & permissions',
            'manage-agents' => 'Agents',
            'manage-staff' => 'Staff',
            'manage-games' => 'Games',
            'manage-events' => 'Events',
            'manage-cockpits' => 'Cockpits',
            'manage-cockpit-presets' => 'Cockpit presets',
            'manage-odds-tiers' => 'Odds tiers',
            'manage-rfid-terminals' => 'RFID terminals',
            'manage-wallets' => 'Wallets',
            'manage-settings' => 'Payout settings',
            'manage-approval-pin' => 'Approval PIN',
            'view-audit-log' => 'Audit trail',
            'view-reports' => 'Reports',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNames(): array
    {
        return array_keys(self::permissionLabels());
    }

    public function index(): View
    {
        $labels = self::permissionLabels();
        $roles = Role::with('permissions')->orderBy('name')->get();

        return view('superadmin.roles.index', compact('roles', 'labels'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => 'sometimes|array',
            'permissions.*' => [Rule::in(self::permissionNames())],
        ]);

        $newPermissions = $data['permissions'] ?? [];

        // The superadmin role is this screen's own gate (manage-roles) —
        // losing it here would lock every superadmin out of this page
        // with no code-level way back in short of a migration/tinker
        // session. webmaster and every other role stay fully editable,
        // including removable from manage-roles itself.
        if ($role->name === 'superadmin' && ! in_array('manage-roles', $newPermissions, true)) {
            return back()->with('error', __("The superadmin role must keep the \"Manage roles & permissions\" permission."));
        }

        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $role->syncPermissions($newPermissions);
        $after = $role->permissions()->get()->pluck('name')->sort()->values()->all();

        AuditLogger::log(
            action: 'role.permissions_updated',
            description: __('Permissions updated for role :name.', ['name' => $role->name]),
            target: $role,
            changes: ['permissions' => ['old' => $before, 'new' => $after]],
        );

        return back()->with('success', __('Permissions updated for :name.', ['name' => $role->name]));
    }
}
