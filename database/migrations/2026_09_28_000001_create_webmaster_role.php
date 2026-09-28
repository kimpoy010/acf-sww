<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the 'webmaster' Role row the same way the combined-sabong game
 * migration does — as a migration (not just RolesAndUsersSeeder), so the
 * role always exists on any environment that only ever runs `migrate`,
 * and so code that queries it (AdminPinService, the `role:...|webmaster`
 * route middleware) never hits Spatie's RoleDoesNotExist before the
 * seeder has had a chance to run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('roles')->where('name', 'webmaster')->where('guard_name', 'web')->exists();

        if (! $exists) {
            DB::table('roles')->insert([
                'name' => 'webmaster',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'webmaster')->where('guard_name', 'web')->delete();
    }
};
