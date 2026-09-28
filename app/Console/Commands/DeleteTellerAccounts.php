<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\Permission\Models\Role;

/**
 * The app no longer supports teller accounts at all (see routes/web.php's
 * teller prefix group, now an unconditional 404, LoginController's
 * teller-role block, and StaffController no longer offering it as a
 * creatable role) — this permanently removes every existing one.
 *
 * Deleting a teller's User row cascades to their Wallet (cascadeOnDelete)
 * and from there to that wallet's own WalletTransaction rows
 * (cascadeOnDelete) — WalletTransaction is one of this app's hash-chained
 * tables (see HasHashChain), so cascade-deleting from the middle of a
 * chain would corrupt it. In normal operation a teller's own wallet is
 * never actually transacted against (CashTransactionService credits/debits
 * the PLAYER's wallet; a teller's wallet_id is created for every staff
 * account uniformly but never used), so this is expected to never fire —
 * but if it ever does, that one account is deactivated and stripped of
 * its role instead of hard-deleted, rather than silently destroying
 * hash-chained history. Everything else that references a teller (bets'
 * placed/redeemed/voided _by_teller_id, cash_transactions.teller_id,
 * teller_shifts.teller_id) is either nullOnDelete (the record survives,
 * just loses the "who" for that leg) or a non-chained table
 * (teller_shifts itself), so no other data is at risk.
 */
class DeleteTellerAccounts extends Command
{
    use ConfirmableTrait;

    protected $signature = 'teller:delete-accounts {--force : Skip the production confirmation prompt}';

    protected $description = 'Permanently delete every teller-role account — the app no longer supports teller logins';

    public function handle(): int
    {
        // User::role('teller') throws RoleDoesNotExist if the row itself
        // is missing — true on any environment where RolesAndUsersSeeder
        // (which no longer creates it) is the only seeder that's run.
        if (! Role::where('name', 'teller')->exists()) {
            $this->info('No teller accounts found — nothing to delete.');

            return self::SUCCESS;
        }

        $tellers = User::role('teller')->get();

        if ($tellers->isEmpty()) {
            $this->info('No teller accounts found — nothing to delete.');

            return self::SUCCESS;
        }

        if (! $this->confirmToProceed("This permanently deletes {$tellers->count()} teller account(s) — their wallet, teller-shift history, and RFID card link go with them. Bets/cash transactions they handled stay, just without a teller attributed.")) {
            return self::FAILURE;
        }

        $deleted = 0;
        $deactivated = [];

        foreach ($tellers as $teller) {
            $walletTransactionCount = $teller->wallet?->transactions()->count() ?? 0;

            if ($walletTransactionCount > 0) {
                // Never expected (see the class doc comment) — refuse to
                // hard-delete rather than risk cascading into a
                // hash-chained wallet_transactions row.
                $teller->syncRoles([]);
                $teller->update(['status' => 'inactive']);
                $deactivated[] = $teller->username;

                continue;
            }

            $teller->delete();
            $deleted++;
        }

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} teller account(s).");
        }

        foreach ($deactivated as $username) {
            $this->warn("{$username} has wallet transaction history — deactivated and stripped of its role instead of deleted, to keep that history intact.");
        }

        return self::SUCCESS;
    }
}
