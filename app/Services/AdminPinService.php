<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Verifies the PIN typed into an in-person approval prompt (currently: a
 * teller voiding a ticket). The prompt only asks for a PIN, not which
 * admin it belongs to, so every account holding the manage-approval-pin
 * permission (see Superadmin\RoleController — superadmin and webmaster
 * by default) with a PIN set is a candidate — checked one at a time
 * since a bcrypt hash can't be queried against directly. These accounts
 * are few, so this stays cheap.
 */
class AdminPinService
{
    public function findApprover(string $pin): ?User
    {
        return User::permission('manage-approval-pin')
            ->whereNotNull('pin')
            ->get()
            ->first(fn (User $admin) => Hash::check($pin, $admin->pin));
    }
}
