<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\VipRebateLog;
use App\Models\VipTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VipRebateService
{
    public function __construct(private WalletService $walletService) {}

    /**
     * Credit a player's VIP rebate for one settled bet's valid (matched)
     * amount, and roll that amount into their wallet's lifetime_valid_bets
     * running total — which determines the tier used for THIS bet. A bet
     * that itself crosses a tier's threshold is rebated at the tier it
     * just reached, not the one it started in. Returns the rebate amount
     * actually credited (0.0 if the player hasn't reached the lowest tier
     * yet, has no wallet, or the amount is zero/negative).
     */
    public function creditRebate(Bet $bet, float $validAmount): float
    {
        if ($validAmount <= 0 || ! $bet->user_id) {
            return 0.0;
        }

        $player = $bet->user;
        if (! $player || ! $player->wallet) {
            return 0.0;
        }

        return DB::transaction(function () use ($bet, $player, $validAmount) {
            $wallet = $player->wallet()->lockForUpdate()->first();

            $wallet->increment('lifetime_valid_bets', $validAmount);
            $newTotal = (float) $wallet->lifetime_valid_bets;

            $tier = VipTier::forValidBets($newTotal);
            if (! $tier) {
                return 0.0;
            }

            $rebate = round($validAmount * (float) $tier->rebate_percent / 100, 2);
            if ($rebate <= 0) {
                return 0.0;
            }

            $this->walletService->creditBet(
                $wallet,
                $rebate,
                'vip_rebate',
                $bet->id,
                "VIP rebate ({$tier->name}) for Bet #{$bet->id}"
            );

            VipRebateLog::create([
                'fight_id' => $bet->fight_id,
                'player_id' => $player->id,
                'bet_id' => $bet->id,
                'vip_tier_id' => $tier->id,
                'side' => $bet->side,
                'valid_amount' => $validAmount,
                'rebate_percent' => $tier->rebate_percent,
                'amount' => $rebate,
                'credited_at' => now(),
            ]);

            Log::info('vip_rebate.credited', [
                'player_id' => $player->id,
                'bet_id' => $bet->id,
                'tier' => $tier->name,
                'amount' => $rebate,
            ]);

            return $rebate;
        });
    }
}
