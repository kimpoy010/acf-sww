<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Verifies the PIN a superadmin (or webmaster — same access level for now,
 * see RolesAndUsersSeeder) types into an in-person approval prompt
 * (currently: a teller voiding a ticket). The prompt only asks for a PIN,
 * not which admin it belongs to, so every superadmin/webmaster with a PIN
 * set is a candidate — checked one at a time since a bcrypt hash can't be
 * queried against directly. These accounts are few, so this stays cheap.
 */
class AdminPinService
{
    public function findApprover(string $pin): ?User
    {
        return User::role(['superadmin', 'webmaster'])
            ->whereNotNull('pin')
            ->get()
            ->first(fn (User $admin) => Hash::check($pin, $admin->pin));
    }
}
