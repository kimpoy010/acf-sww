<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Lets a player set/change the wallet withdrawal PIN
 * CashController::storeWithdrawal() requires before a withdrawal goes
 * through — shown on the Profile page (see player/profile/show.blade.php).
 *
 * Deliberately doesn't ask for the current PIN to change it, same
 * reasoning as AccountController::updatePassword(): the session is
 * already proof of who's asking.
 */
class WalletPinController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        $player = $request->user();
        $hadPin = $player->hasWalletPin();
        $player->update(['pin' => Hash::make($data['pin'])]);

        AuditLogger::log(
            action: 'account.wallet_pin_changed',
            description: __(':name :action their wallet withdrawal PIN.', [
                'name' => $player->displayName(),
                'action' => $hadPin ? __('changed') : __('set'),
            ]),
            target: $player,
        );

        return redirect()->route('play.profile')->with('success', $hadPin ? __('Withdrawal PIN updated.') : __('Withdrawal PIN set.'));
    }
}
