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
        // 'teller' is deliberately absent — the app no longer supports
        // teller accounts (see Console\Commands\DeleteTellerAccounts and
        // the /teller route group's own unconditional 404). Its Role row
        // is left alone wherever it already exists (nothing here deletes
        // it), just never (re)assigned to a demo account by this seeder.
        foreach (['player', 'declarator', 'superadmin', 'agent', 'webmaster'] as $role) {
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
            ]
        );
        if (! $superadmin->hasRole('superadmin')) {
            $superadmin->assignRole('superadmin');
        }
        Wallet::firstOrCreate(['user_id' => $superadmin->id], ['main_balance' => 1_000_000]);

        // This app's top-level/root account — granted every admin
        // permission by the 2026_09_28_000002 RBAC migration, and the one
        // role Superadmin\RoleController's own safeguard never lets lose
        // manage-roles (see its update() method), so it can always reach
        // the Roles & Permissions screen to fix any other role's access,
        // superadmin included. Its password is randomly generated rather
        // than the demo accounts'
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
