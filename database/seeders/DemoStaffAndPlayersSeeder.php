<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DemoStaffAndPlayersSeeder extends Seeder
{
    /**
     * Ten player accounts for local demos and manual testing —
     * RolesAndUsersSeeder already gives you one (player@example.com);
     * this fills out a bigger roster so a flow like player search has
     * more than a single record to work against.
     *
     * Used to also seed ten demo teller accounts here — dropped along
     * with the rest of teller support (see
     * Console\Commands\DeleteTellerAccounts); assigning a 'teller' role
     * that may no longer even exist as a Role row would throw
     * Spatie's RoleDoesNotExist on a fresh environment.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $player = User::firstOrCreate(
                ['email' => "player{$i}@example.com"],
                [
                    'name' => "Demo Player {$i}",
                    'username' => "player{$i}",
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
}
