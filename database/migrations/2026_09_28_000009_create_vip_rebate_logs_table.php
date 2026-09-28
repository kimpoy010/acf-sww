<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per bet-settlement rebate credit — the audit trail behind
 * VipRebateService::creditRebate(), same role for VIP rebates that
 * CommissionLog plays for agent commission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_rebate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bet_id')->constrained()->cascadeOnDelete();
            // nullOnDelete, not cascade — deleting/renaming a tier later
            // must never erase the historical record of what was actually
            // paid out under it.
            $table->foreignId('vip_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('side', ['meron', 'wala']);
            $table->decimal('valid_amount', 12, 2);
            $table->decimal('rebate_percent', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamp('credited_at');
            $table->timestamps();

            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_rebate_logs');
    }
};
