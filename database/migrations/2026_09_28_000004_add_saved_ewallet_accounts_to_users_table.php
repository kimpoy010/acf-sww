<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A player/agent registers their own GCash number and Maya
 * number+name once (see Player\PaymentMethodController); the Cash page
 * then pre-fills a deposit with it and, for withdrawals, is locked to
 * paying out to exactly this saved account rather than whatever a
 * withdrawal request happens to type in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gcash_account_number', 32)->nullable()->after('pin');
            $table->string('maya_account_number', 32)->nullable()->after('gcash_account_number');
            $table->string('maya_account_name', 191)->nullable()->after('maya_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gcash_account_number', 'maya_account_number', 'maya_account_name']);
        });
    }
};
