<?php

namespace App\Services\Paybucks;

/**
 * This app only integrates two of Paybucks' many channels — GCash and
 * Maya — out of its full deposit/withdrawal service-type catalog (see the
 * vendor's Merchant API doc, section 6). Everything channel-specific
 * (which serviceType id, which extra fields are required) lives here so
 * CashTransactionService and the controllers never hardcode a raw id.
 */
class PaybucksChannel
{
    public const GCASH = 'gcash';

    public const MAYA = 'maya';

    public const CHANNELS = [self::GCASH, self::MAYA];

    public static function depositServiceType(string $channel): int
    {
        return match ($channel) {
            self::GCASH => 1,
            self::MAYA => 502,
            default => throw new \InvalidArgumentException("Unsupported deposit channel: {$channel}"),
        };
    }

    /**
     * Paybucks has no dedicated Maya wallet-withdraw channel the way GCash
     * has serviceType 2 — a Maya cash-out routes through the bank
     * withdrawal table instead, as "Maya Philippines, Inc." (serviceType
     * 289), which is why it needs account_name and GCash's withdraw
     * doesn't.
     */
    public static function withdrawServiceType(string $channel): int
    {
        return match ($channel) {
            self::GCASH => 2,
            self::MAYA => 289,
            default => throw new \InvalidArgumentException("Unsupported withdrawal channel: {$channel}"),
        };
    }

    /**
     * Per the channel table: GCash deposit (serviceType 1) needs the
     * payer's own GCash number; Maya deposit (serviceType 502) needs no
     * extra field at all (it's a scan-with-any-Maya-app QR).
     */
    public static function depositRequiresAccountNumber(string $channel): bool
    {
        return $channel === self::GCASH;
    }

    /**
     * Every withdrawal needs a destination account_number regardless of
     * channel; only the bank-rail Maya payout (289) also needs the
     * beneficiary's account_name.
     */
    public static function withdrawalRequiresAccountName(string $channel): bool
    {
        return $channel === self::MAYA;
    }

    public static function label(string $channel): string
    {
        return match ($channel) {
            self::GCASH => 'GCash',
            self::MAYA => 'Maya',
            default => ucfirst($channel),
        };
    }
}
