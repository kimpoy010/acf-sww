<?php

namespace App\Services;

use App\Events\CashTransactionUpdated;
use App\Models\CashTransaction;
use App\Models\TellerShift;
use App\Models\User;
use App\Services\Paybucks\PaybucksChannel;
use App\Services\Paybucks\PaybucksClient;
use App\Services\Paybucks\PaybucksException;
use App\Support\AuditLogger;
use App\Support\Broadcaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashTransactionService
{
    public const EXPIRES_IN_MINUTES = 15;

    public function __construct(private WalletService $walletService) {}

    /**
     * Start a deposit ("cash in") request. Nothing is credited yet — that
     * only happens once a teller scans the QR (or looks up an RFID-kiosk
     * request), receives payment, and approves it.
     */
    public function createDeposit(User $player, float $amount, string $origin = 'teller'): CashTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(__('Deposit amount must be positive.'));
        }

        $this->assertNoPendingRequest($player);

        $transaction = CashTransaction::create([
            'user_id' => $player->id,
            'type' => 'deposit',
            'origin' => $origin,
            'amount' => $amount,
            'code' => $this->generateCode(),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
        ]);

        AuditLogger::log(
            action: 'cash.deposit_requested',
            description: __(':name requested a :amount deposit.', ['name' => $player->displayName(), 'amount' => $amount]),
            target: $transaction,
            actor: $player,
        );

        return $transaction;
    }

    /**
     * Start a withdrawal ("cash out") request for the player's full
     * available balance. The amount is snapshotted and immediately
     * reserved (held out of main_balance's *available* portion) so it
     * can't be bet away or double-withdrawn while the request is pending —
     * but main_balance itself isn't touched until a teller approves it.
     */
    public function createWithdrawal(User $player): CashTransaction
    {
        $this->assertNoPendingRequest($player);

        $wallet = $player->wallet;
        $amount = $wallet->availableBalance();

        if ($amount <= 0) {
            throw new \InvalidArgumentException(__('No available balance to withdraw.'));
        }

        $transaction = DB::transaction(function () use ($player, $wallet, $amount) {
            $this->walletService->reserveWithdrawal($wallet, $amount);

            return CashTransaction::create([
                'user_id' => $player->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'code' => $this->generateCode(),
                'status' => 'pending',
                'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
            ]);
        });

        AuditLogger::log(
            action: 'cash.withdrawal_requested',
            description: __(':name requested a :amount withdrawal.', ['name' => $player->displayName(), 'amount' => $amount]),
            target: $transaction,
            actor: $player,
        );

        return $transaction;
    }

    /**
     * Start a GCash/Maya deposit via Paybucks. Unlike the teller flow, this
     * calls out to Paybucks immediately — the returned CashTransaction
     * carries whatever paymentUrl/qrPayload/qrImageUrl the response gave,
     * for the player's show page to render. Nothing is credited until
     * reconcilePaybucksOrder() confirms SUCCESS (via the callback or a
     * status poll) — same as the teller flow never touching main_balance
     * before an approve().
     */
    public function createPaybucksDeposit(User $player, float $amount, string $channel, ?string $accountNumber): CashTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(__('Deposit amount must be positive.'));
        }

        if (! in_array($channel, PaybucksChannel::CHANNELS, true)) {
            throw new \InvalidArgumentException(__('Unsupported payment channel.'));
        }

        if (PaybucksChannel::depositRequiresAccountNumber($channel) && ! filled($accountNumber)) {
            throw new \InvalidArgumentException(__(':channel number is required.', ['channel' => PaybucksChannel::label($channel)]));
        }

        $this->assertNoPendingRequest($player);

        $transaction = CashTransaction::create([
            'user_id' => $player->id,
            'type' => 'deposit',
            'origin' => 'paybucks',
            'provider' => 'paybucks',
            'channel' => $channel,
            'service_type' => PaybucksChannel::depositServiceType($channel),
            'amount' => $amount,
            'account_number' => $accountNumber,
            'code' => $this->generateCode(),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
        ]);

        try {
            $response = PaybucksClient::make()->deposit(array_filter([
                'merchantOrderNo' => $transaction->code,
                'serviceType' => $transaction->service_type,
                'amount' => $amount,
                'hashedMemId' => $this->hashedMemberId($player),
                'merchantUser' => $player->displayName(),
                'account_number' => $accountNumber,
                'callbackUrl' => route('api.paybucks.callback.deposit'),
            ], fn ($value) => $value !== null));
        } catch (PaybucksException $e) {
            $transaction->update(['status' => 'failed', 'provider_error_msg' => $e->getMessage()]);
            Log::warning('paybucks.deposit_start_failed', ['code' => $transaction->code, 'error' => $e->getMessage()]);

            throw new \InvalidArgumentException(__('Could not start the deposit right now. Please try again.'));
        }

        $transaction->update([
            'provider_transaction_id' => $response['transactionId'] ?? null,
            'payment_url' => $response['paymentUrl'] ?? null,
            'qr_payload' => $response['qrPayload'] ?? null,
            'qr_image_url' => $response['qrImageUrl'] ?? null,
        ]);

        if (blank($response['paymentUrl'] ?? null) && blank($response['qrPayload'] ?? null) && blank($response['qrImageUrl'] ?? null)) {
            // Paybucks accepted the request (no PaybucksException) but gave
            // us nothing to show the player to actually pay with — either
            // this channel/environment responds under different field
            // names than the doc's paymentUrl/qrPayload/qrImageUrl, or it
            // genuinely omitted them. Logging the raw body is the fastest
            // way to tell which from the field names actually present.
            Log::warning('paybucks.deposit_response_missing_payment_fields', [
                'code' => $transaction->code,
                'channel' => $channel,
                'response' => $response,
            ]);
        }

        AuditLogger::log(
            action: 'cash.deposit_requested',
            description: __(':name requested a :amount :channel deposit.', ['name' => $player->displayName(), 'amount' => $amount, 'channel' => PaybucksChannel::label($channel)]),
            target: $transaction,
            actor: $player,
        );

        return $transaction->fresh();
    }

    /**
     * Start a GCash/Maya withdrawal via Paybucks, for the player's full
     * available balance — same "no partial withdrawal" shape as the
     * teller flow's createWithdrawal(). The reservation happens up front
     * (same as the teller flow); main_balance itself is only debited once
     * reconcilePaybucksOrder() confirms Paybucks actually paid it out.
     */
    public function createPaybucksWithdrawal(User $player, string $channel, string $accountNumber, ?string $accountName): CashTransaction
    {
        if (! in_array($channel, PaybucksChannel::CHANNELS, true)) {
            throw new \InvalidArgumentException(__('Unsupported payment channel.'));
        }

        if (! filled($accountNumber)) {
            throw new \InvalidArgumentException(__(':channel number is required.', ['channel' => PaybucksChannel::label($channel)]));
        }

        if (PaybucksChannel::withdrawalRequiresAccountName($channel) && ! filled($accountName)) {
            throw new \InvalidArgumentException(__('Account holder name is required for :channel withdrawals.', ['channel' => PaybucksChannel::label($channel)]));
        }

        $this->assertNoPendingRequest($player);

        $wallet = $player->wallet;
        $amount = $wallet->availableBalance();

        if ($amount <= 0) {
            throw new \InvalidArgumentException(__('No available balance to withdraw.'));
        }

        $transaction = DB::transaction(function () use ($player, $wallet, $amount, $channel, $accountNumber, $accountName) {
            $this->walletService->reserveWithdrawal($wallet, $amount);

            return CashTransaction::create([
                'user_id' => $player->id,
                'type' => 'withdrawal',
                'origin' => 'paybucks',
                'provider' => 'paybucks',
                'channel' => $channel,
                'service_type' => PaybucksChannel::withdrawServiceType($channel),
                'amount' => $amount,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'code' => $this->generateCode(),
                'status' => 'pending',
                'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
            ]);
        });

        try {
            $response = PaybucksClient::make()->withdraw(array_filter([
                'merchantOrderNo' => $transaction->code,
                'serviceType' => $transaction->service_type,
                'amount' => $amount,
                'merchantUser' => $player->displayName(),
                'hashedMemId' => $this->hashedMemberId($player),
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'callbackUrl' => route('api.paybucks.callback.withdrawal'),
            ], fn ($value) => $value !== null));
        } catch (PaybucksException $e) {
            DB::transaction(function () use ($transaction, $wallet, $amount, $e) {
                $this->walletService->releaseWithdrawalReservation($wallet, $amount);
                $transaction->update(['status' => 'failed', 'provider_error_msg' => $e->getMessage()]);
            });
            Log::warning('paybucks.withdrawal_start_failed', ['code' => $transaction->code, 'error' => $e->getMessage()]);

            throw new \InvalidArgumentException(__('Could not start the withdrawal right now. Please try again.'));
        }

        $transaction->update([
            'provider_transaction_id' => $response['transactionId'] ?? null,
            'fee' => $response['fee'] ?? null,
        ]);

        AuditLogger::log(
            action: 'cash.withdrawal_requested',
            description: __(':name requested a :amount :channel withdrawal.', ['name' => $player->displayName(), 'amount' => $amount, 'channel' => PaybucksChannel::label($channel)]),
            target: $transaction,
            actor: $player,
        );

        return $transaction->fresh();
    }

    /**
     * The one place a Paybucks-origin CashTransaction is ever settled.
     * Deliberately never trusts the inbound callback's own payload for
     * the actual outcome — Paybucks documents no signature on that
     * webhook, so anyone who found the URL and a valid merchantOrderNo
     * could otherwise forge a "SUCCESS" POST. Instead this re-asks
     * Paybucks itself, with our own API key, via the Order Status API,
     * and only acts on THAT. Safe to call more than once for the same
     * transaction (idempotent via the isPending() guard under lock) — the
     * callback controller calls this on every delivery attempt, and the
     * player's own status-polling page calls it too, as a fallback in
     * case a callback is ever lost.
     */
    public function reconcilePaybucksOrder(CashTransaction $transaction): CashTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $locked = CashTransaction::lockForUpdate()->findOrFail($transaction->id);

            if (! $locked->isPending()) {
                return $locked;
            }

            try {
                $order = PaybucksClient::make()->orderStatus($locked->code);
            } catch (PaybucksException $e) {
                // Can't confirm right now — leave it pending. A retried
                // callback or the next status poll tries again; never
                // guess at an outcome we can't verify.
                Log::warning('paybucks.order_status_failed', ['code' => $locked->code, 'error' => $e->getMessage()]);

                return $locked;
            }

            $status = $order['status'] ?? null;

            if ($status === 'PENDING' || $status === null) {
                return $locked;
            }

            if ($status === 'SUCCESS') {
                if ($locked->type === 'deposit') {
                    $this->walletService->credit(
                        $locked->user->wallet,
                        (float) $locked->amount,
                        'deposit',
                        $locked->id,
                        __(':channel deposit via Paybucks', ['channel' => PaybucksChannel::label($locked->channel)])
                    );
                } else {
                    $this->walletService->completeWithdrawal(
                        $locked->user->wallet,
                        (float) $locked->amount,
                        $locked->id,
                        __(':channel withdrawal via Paybucks', ['channel' => PaybucksChannel::label($locked->channel)])
                    );
                }

                $locked->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'provider_error_code' => $order['providerErrorCode'] ?? null,
                    'provider_error_msg' => $order['providerErrorMsg'] ?? null,
                ]);
            } else {
                // FAILED or PARTIAL — never partially credit/debit on an
                // ambiguous provider outcome. A withdrawal's reservation is
                // released (the money never actually left, since we only
                // debit main_balance on a confirmed SUCCESS); a deposit
                // simply never happened.
                if ($locked->type === 'withdrawal') {
                    $this->walletService->releaseWithdrawalReservation($locked->user->wallet, (float) $locked->amount);
                }

                $locked->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'provider_error_code' => $order['providerErrorCode'] ?? null,
                    'provider_error_msg' => $order['providerErrorMsg'] ?? "Paybucks reported status: {$status}",
                ]);
            }

            $fresh = $locked->fresh();

            AuditLogger::log(
                action: 'cash.'.$fresh->status,
                description: __('Paybucks :channel :type :status for :amount.', [
                    'channel' => PaybucksChannel::label($fresh->channel),
                    'type' => $fresh->type,
                    'status' => $fresh->status,
                    'amount' => $fresh->amount,
                ]),
                target: $fresh,
                actor: $fresh->user,
            );

            DB::afterCommit(fn () => Broadcaster::send(new CashTransactionUpdated($fresh)));

            return $fresh;
        });
    }

    /**
     * Stable per-player identifier Paybucks asks for (hashedMemId) —
     * never the raw id, though it's not meant to be a secret either;
     * just what their fraud/dedup tooling keys on.
     */
    private function hashedMemberId(User $player): string
    {
        return hash('sha256', 'user-'.$player->id);
    }

    /**
     * Credit a player's wallet immediately — one step, no pending/approve
     * split. For a teller-initiated deposit where the teller is physically
     * handing the transaction right now (e.g. an RFID-identified player at
     * the counter): the teller present *is* the approver, so there's
     * nothing to separately approve later. Still recorded as a completed
     * CashTransaction against the teller's shift, exactly like an approved
     * QR-flow deposit, so shift reconciliation and history look identical
     * either way.
     */
    public function instantDeposit(User $player, float $amount, User $teller, TellerShift $shift, string $origin = 'teller'): CashTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(__('Deposit amount must be positive.'));
        }

        $this->assertNoPendingRequest($player);

        return DB::transaction(function () use ($player, $amount, $teller, $shift, $origin) {
            $this->walletService->credit(
                $player->wallet,
                $amount,
                'deposit',
                null,
                "Cash deposit via teller {$teller->displayName()}"
            );

            $transaction = CashTransaction::create([
                'user_id' => $player->id,
                'type' => 'deposit',
                'origin' => $origin,
                'amount' => $amount,
                'code' => $this->generateCode(),
                'status' => 'completed',
                'teller_id' => $teller->id,
                'teller_shift_id' => $shift->id,
                'expires_at' => now(),
                'completed_at' => now(),
            ]);

            DB::afterCommit(fn () => Broadcaster::send(new CashTransactionUpdated($transaction)));

            return $transaction;
        });
    }

    /**
     * Withdraw a player's full available balance immediately — one step,
     * same reasoning as instantDeposit(): the teller handing over the cash
     * right now is the approval.
     */
    public function instantWithdrawal(User $player, User $teller, TellerShift $shift, string $origin = 'teller'): CashTransaction
    {
        $this->assertNoPendingRequest($player);

        $wallet = $player->wallet;
        $amount = $wallet->availableBalance();

        if ($amount <= 0) {
            throw new \InvalidArgumentException(__('No available balance to withdraw.'));
        }

        return DB::transaction(function () use ($player, $wallet, $amount, $teller, $shift, $origin) {
            // Reserve-then-complete in the same transaction nets out to a
            // plain immediate debit, while reusing the existing balance
            // check and wallet bookkeeping instead of duplicating it.
            $this->walletService->reserveWithdrawal($wallet, $amount);
            $this->walletService->completeWithdrawal(
                $wallet,
                $amount,
                null,
                "Cash withdrawal via teller {$teller->displayName()}"
            );

            $transaction = CashTransaction::create([
                'user_id' => $player->id,
                'type' => 'withdrawal',
                'origin' => $origin,
                'amount' => $amount,
                'code' => $this->generateCode(),
                'status' => 'completed',
                'teller_id' => $teller->id,
                'teller_shift_id' => $shift->id,
                'expires_at' => now(),
                'completed_at' => now(),
            ]);

            DB::afterCommit(fn () => Broadcaster::send(new CashTransactionUpdated($transaction)));

            return $transaction;
        });
    }

    /**
     * Cancel a pending request — callable by the player who owns it (changed
     * their mind) or the teller who scanned it (invalid/no-show). Releases a
     * withdrawal's reservation without ever touching main_balance.
     */
    public function cancel(CashTransaction $transaction): CashTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $locked = CashTransaction::lockForUpdate()->findOrFail($transaction->id);

            if (! $locked->isPending()) {
                throw new \InvalidArgumentException(__('Only a pending request can be cancelled.'));
            }

            if ($locked->type === 'withdrawal') {
                $this->walletService->releaseWithdrawalReservation($locked->user->wallet, (float) $locked->amount);
            }

            $locked->update(['status' => 'cancelled']);
            $fresh = $locked->fresh();

            AuditLogger::log(
                action: 'cash.request_cancelled',
                description: __('Cancelled a :type request for :amount.', ['type' => $fresh->type, 'amount' => $fresh->amount]),
                target: $fresh,
            );

            DB::afterCommit(fn () => Broadcaster::send(new CashTransactionUpdated($fresh)));

            return $fresh;
        });
    }

    /**
     * Approve a pending request at the teller's counter: credits the
     * player's wallet for a deposit, or actually debits it (releasing the
     * reservation) for a withdrawal. When the teller has an open POS shift,
     * pass it so the cash movement counts toward that shift's end-of-shift
     * reconciliation.
     */
    public function approve(CashTransaction $transaction, User $teller, ?TellerShift $shift = null): CashTransaction
    {
        return DB::transaction(function () use ($transaction, $teller, $shift) {
            $locked = CashTransaction::lockForUpdate()->findOrFail($transaction->id);

            if ($locked->isExpired()) {
                $this->expire($locked);
                throw new \InvalidArgumentException(__('This request has expired.'));
            }

            if (! $locked->isPending()) {
                throw new \InvalidArgumentException(__('This request has already been processed.'));
            }

            $wallet = $locked->user->wallet;

            if ($locked->type === 'deposit') {
                $this->walletService->credit(
                    $wallet,
                    (float) $locked->amount,
                    'deposit',
                    $locked->id,
                    "Cash deposit via teller {$teller->displayName()}"
                );
            } else {
                $this->walletService->completeWithdrawal(
                    $wallet,
                    (float) $locked->amount,
                    $locked->id,
                    "Cash withdrawal via teller {$teller->displayName()}"
                );
            }

            $locked->update([
                'status' => 'completed',
                'teller_id' => $teller->id,
                'teller_shift_id' => $shift?->id,
                'completed_at' => now(),
            ]);

            DB::afterCommit(fn () => Broadcaster::send(new CashTransactionUpdated($locked->fresh())));

            return $locked->fresh();
        });
    }

    /**
     * Lazily mark a stale pending request expired (called whenever one is
     * looked up past its expiry) and release any withdrawal reservation.
     */
    public function expire(CashTransaction $transaction): CashTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $locked = CashTransaction::lockForUpdate()->findOrFail($transaction->id);

            if (! $locked->isPending() || ! $locked->isExpired()) {
                return $locked;
            }

            if ($locked->type === 'withdrawal') {
                $this->walletService->releaseWithdrawalReservation($locked->user->wallet, (float) $locked->amount);
            }

            $locked->update(['status' => 'expired']);
            $fresh = $locked->fresh();

            AuditLogger::log(
                action: 'cash.request_expired',
                description: __('A :type request for :amount expired unapproved.', ['type' => $fresh->type, 'amount' => $fresh->amount]),
                target: $fresh,
                actor: $fresh->user,
            );

            DB::afterCommit(fn () => Broadcaster::send(new CashTransactionUpdated($fresh)));

            return $fresh;
        });
    }

    private function assertNoPendingRequest(User $player): void
    {
        $existing = CashTransaction::where('user_id', $player->id)->where('status', 'pending')->first();

        if (! $existing) {
            return;
        }

        if ($existing->isExpired()) {
            $this->expire($existing);

            return;
        }

        throw new \InvalidArgumentException(__('You already have a pending cash request. Cancel it before starting a new one.'));
    }

    private function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (CashTransaction::where('code', $code)->exists());

        return $code;
    }
}
