<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A player VIP rebate tier — how much of their lifetime "valid" (matched)
 * wagering it takes to reach it, and what percentage of every matched bet
 * they earn back once there. Superadmin-managed (see Superadmin\
 * VipTierController); which tier a player is currently in is never stored
 * on the player themselves — it's always resolved live from their wallet's
 * lifetime_valid_bets total against these rows (see VipTier::forValidBets()),
 * so editing a tier's threshold here retroactively re-ranks every player
 * without a data migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_valid_bets', 14, 2);
            // Display-only — tier resolution only ever looks at
            // min_valid_bets (the highest one a player's total clears), so
            // a total past every tier's max still lands on the top tier
            // instead of falling through to none.
            $table->decimal('max_valid_bets', 14, 2)->nullable();
            $table->decimal('rebate_percent', 5, 2);
            $table->timestamps();
        });

        // Seeded here too (not just VipTierSeeder) so the starting tiers
        // exist on any environment that only ever runs `migrate` — same
        // reasoning as 2026_09_27_000007_seed_combined_sabong_game.
        $now = now();
        DB::table('vip_tiers')->insert([
            ['name' => 'VIP', 'min_valid_bets' => 5000, 'max_valid_bets' => 999999.99, 'rebate_percent' => 0.5, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'VIP 1', 'min_valid_bets' => 1000000, 'max_valid_bets' => 4999999.99, 'rebate_percent' => 0.7, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'VIP 2', 'min_valid_bets' => 5000000, 'max_valid_bets' => 9999999.99, 'rebate_percent' => 0.8, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'VIP 3', 'min_valid_bets' => 10000000, 'max_valid_bets' => 19999999.99, 'rebate_percent' => 0.9, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'SUPER VIP', 'min_valid_bets' => 20000000, 'max_valid_bets' => 50000000, 'rebate_percent' => 1.0, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_tiers');
    }
};
