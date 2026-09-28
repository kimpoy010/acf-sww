<?php

namespace App\Models;

use App\Models\Concerns\HasHashChain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasHashChain;

    protected $fillable = [
        'user_id',
        'teller_id',
        'teller_shift_id',
        'type',
        'origin',
        'amount',
        'code',
        'status',
        'expires_at',
        'completed_at',
        'provider',
        'channel',
        'service_type',
        'account_number',
        'account_name',
        'provider_transaction_id',
        'fee',
        'provider_error_code',
        'provider_error_msg',
        'qr_payload',
        'qr_image_url',
        'payment_url',
    ];

    /**
     * Deliberately unchanged from before the Paybucks columns existed —
     * VerifyHashChains recomputes every row's hash from THIS method's
     * CURRENT return value, so adding a field here would break
     * verification for every row already hashed under the old set (their
     * stored hash never covered it). The new Paybucks columns
     * (channel, service_type, account_number, account_name,
     * provider_transaction_id, fee, provider_error_*, qr_*, payment_url)
     * stay outside the tamper-evident set — routing/provider metadata,
     * not the money-movement fact itself, which is covered separately by
     * its own WalletTransaction chain entry once a deposit/withdrawal
     * actually settles.
     */
    public function hashChainFields(): array
    {
        return ['user_id', 'type', 'origin', 'amount', 'code', 'expires_at'];
    }

    /**
     * One chain per player — a player can only ever have one pending
     * request at a time (see CashTransactionService::assertNoPendingRequest),
     * so this scope never sees real contention.
     */
    public function hashChainScope(): string
    {
        return 'user:'.$this->user_id;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Route models by the random `code` (as embedded in the QR/scan URL)
     * rather than the numeric id — used wherever a route binds
     * {cashTransaction:code}.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function tellerShift(): BelongsTo
    {
        return $this->belongsTo(TellerShift::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        if ($this->type === 'withdrawal' && $this->provider === 'paybucks') {
            // Already submitted to Paybucks — the payout may be mid-flight
            // to GCash/Maya. Only reconcilePaybucksOrder() (via callback or
            // a status poll) may resolve this from here; auto-expiring
            // would release the wallet reservation while money could still
            // land on the provider's side, risking a double-spend.
            return false;
        }

        return $this->isPending() && $this->expires_at->isPast();
    }
}
