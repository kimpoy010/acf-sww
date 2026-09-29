<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\VipRebateLog;
use App\Models\VipTier;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    /**
     * The player's "profile QR" — shown to a teller so they can identify
     * the player instantly (e.g. when linking an RFID card) without typing
     * or searching a username.
     */
    public function show(): View
    {
        $player = auth()->user();
        $player->profileCode(); // ensure it exists before rendering the QR

        $lifetimeValidBets = (float) ($player->wallet->lifetime_valid_bets ?? 0);
        $vipTier = VipTier::forValidBets($lifetimeValidBets);
        $nextVipTier = $vipTier ? $vipTier->next() : VipTier::orderBy('min_valid_bets')->first();
        $allVipTiers = VipTier::orderBy('min_valid_bets')->get();

        // Total VIP rebate ever credited to this player, across every tier
        // they've passed through — shown alongside the current tier/progress
        // so a player can see the rebate feature has actually paid out, not
        // just that it exists.
        $totalRebateEarned = (float) VipRebateLog::where('player_id', $player->id)->sum('amount');

        return view('player.profile.show', [
            'player' => $player,
            'lifetimeValidBets' => $lifetimeValidBets,
            'vipTier' => $vipTier,
            'nextVipTier' => $nextVipTier,
            'allVipTiers' => $allVipTiers,
            'totalRebateEarned' => $totalRebateEarned,
        ]);
    }
}
