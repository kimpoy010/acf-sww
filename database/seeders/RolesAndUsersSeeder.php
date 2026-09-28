<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['player', 'declarator', 'superadmin', 'agent', 'teller', 'webmaster'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'referral_code' => 'ROOTADMIN',
                // Demo-only approval PIN, used to sign off a teller's void
                // request in person — change it via Approval PIN in a real
                // deployment.
                'pin' => '1234',
            ]
        );
        if (! $superadmin->hasRole('superadmin')) {
            $superadmin->assignRole('superadmin');
        }
        if (! $superadmin->hasPin()) {
            $superadmin->update(['pin' => '1234']);
        }
        Wallet::firstOrCreate(['user_id' => $superadmin->id], ['main_balance' => 1_000_000]);

        // Same access level as superadmin for now — the webmaster role is
        // granted every admin permission by the 2026_09_28_000002 RBAC
        // migration, same as superadmin. Scope it down from the
        // superadmin > Roles & Permissions screen (Superadmin\RoleController)
        // whenever that's wanted, rather than editing that migration or
        // this seeder. Its password is randomly generated rather than the
        // demo accounts'
        // fixed 'password' since this one is meant to actually be used;
        // printed once, on the run that creates the account, since there's
        // nowhere else this seeder could hand it back afterward.
        $webmasterPassword = Str::password(20);
        $webmaster = User::firstOrCreate(
            ['email' => 'webmaster@example.com'],
            [
                'name' => 'Webmaster',
                'username' => 'webmaster',
                'password' => bcrypt($webmasterPassword),
                'email_verified_at' => now(),
                'referral_code' => 'WEBMASTER',
            ]
        );
        if (! $webmaster->hasRole('webmaster')) {
            $webmaster->assignRole('webmaster');
        }
        Wallet::firstOrCreate(['user_id' => $webmaster->id]);

        if ($webmaster->wasRecentlyCreated) {
            $this->command?->warn("Webmaster account created — username: webmaster  password: {$webmasterPassword}");
            $this->command?->warn('Save that password now — it is only ever shown here, at creation.');
        }

        $declarator = User::firstOrCreate(
            ['email' => 'declarator@example.com'],
            [
                'name' => 'Fight Declarator',
                'username' => 'declarator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $declarator->hasRole('declarator')) {
            $declarator->assignRole('declarator');
        }
        Wallet::firstOrCreate(['user_id' => $declarator->id]);

        $teller = User::firstOrCreate(
            ['email' => 'teller@example.com'],
            [
                'name' => 'Cashier Teller',
                'username' => 'teller',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $teller->hasRole('teller')) {
            $teller->assignRole('teller');
        }
        Wallet::firstOrCreate(['user_id' => $teller->id]);

        $player = User::firstOrCreate(
            ['email' => 'player@example.com'],
            [
                'name' => 'Demo Player',
                'username' => 'player',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        if (! $player->hasRole('player')) {
            $player->assignRole('player');
        }
        Wallet::firstOrCreate(['user_id' => $player->id], ['main_balance' => 1000]);
    }
}
