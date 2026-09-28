<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the fields a Paybucks-backed deposit/withdrawal needs, alongside
 * the existing teller-flow columns. `status` widens from a DB-level enum
 * to a plain string to fit a new 'failed' value (a GCash/Maya request
 * Paybucks itself rejected or reported FAILED/PARTIAL on) — same
 * enum-alteration dodge as fights.status in
 * 2026_09_20_092337_widen_fights_status_column_for_last_call.php; valid
 * values stay enforced at the application layer
 * (CashTransactionService/CashTransaction::isPending()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();

            $table->string('provider', 20)->nullable()->after('origin');
            $table->string('channel', 20)->nullable()->after('provider');
            $table->unsignedInteger('service_type')->nullable()->after('channel');
            $table->string('account_number', 32)->nullable()->after('service_type');
            $table->string('account_name', 191)->nullable()->after('account_number');
            $table->string('provider_transaction_id', 64)->nullable()->after('account_name');
            $table->decimal('fee', 15, 2)->nullable()->after('provider_transaction_id');
            $table->string('provider_error_code', 64)->nullable()->after('fee');
            $table->text('provider_error_msg')->nullable()->after('provider_error_code');
            $table->text('qr_payload')->nullable()->after('provider_error_msg');
            $table->text('qr_image_url')->nullable()->after('qr_payload');
            $table->text('payment_url')->nullable()->after('qr_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'provider', 'channel', 'service_type', 'account_number', 'account_name',
                'provider_transaction_id', 'fee', 'provider_error_code', 'provider_error_msg',
                'qr_payload', 'qr_image_url', 'payment_url',
            ]);
            $table->enum('status', ['pending', 'completed', 'cancelled', 'expired'])->default('pending')->change();
        });
    }
};
