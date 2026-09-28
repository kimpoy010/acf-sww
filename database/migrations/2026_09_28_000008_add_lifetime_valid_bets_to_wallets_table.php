<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Running lifetime total of "valid" (matched) wagering — the amount a
 * player's VIP tier is resolved from (see VipTier::forValidBets()).
 * Incremented atomically alongside the rebate credit itself in
 * VipRebateService::creditRebate(), inside the same settlement
 * transaction as the bet's payout/commission — never recomputed by
 * summing bets on the fly, same convention as main_balance/
 * commission_balance already on this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('lifetime_valid_bets', 14, 2)->default(0)->after('commission_balance');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('lifetime_valid_bets');
        });
    }
};
