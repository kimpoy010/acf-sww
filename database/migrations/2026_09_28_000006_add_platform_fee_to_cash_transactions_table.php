<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The house's own withdrawal fee (see Setting 'withdrawal_fee' /
 * Superadmin\SettingsController) — separate from `fee`, which is
 * whatever Paybucks itself reports on its withdrawal response. Snapshotted
 * onto the row at request time so a later change to the setting never
 * changes what an already-created (or already-settled) withdrawal is
 * recorded as having charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->decimal('platform_fee', 15, 2)->nullable()->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropColumn('platform_fee');
        });
    }
};
