<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Services\Paybucks\PaybucksChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared by both players (play.payment-methods.*) and agents
 * (agent.payment-methods.*) — same controller and view, reached under
 * two prefixes, mirroring Player\CashController's own routePrefix()
 * pattern. Lets a player/agent register the one GCash number and one
 * Maya number+name that CashController's deposit form pre-fills and its
 * withdrawal form is locked to (see User::hasSavedPaymentMethod()).
 */
class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('player.payment-methods.index', [
            'player' => auth()->user(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(PaybucksChannel::CHANNELS)],
            'account_number' => 'required|string|max:32',
            'account_name' => 'nullable|string|max:191',
        ]);

        if ($data['channel'] === PaybucksChannel::MAYA && ! filled($data['account_name'] ?? null)) {
            return redirect()->route($this->routePrefix().'index')->with('error', __('Account holder name is required for Maya.'));
        }

        $player = auth()->user();

        if ($data['channel'] === PaybucksChannel::GCASH) {
            $player->update(['gcash_account_number' => $data['account_number']]);
        } else {
            $player->update([
                'maya_account_number' => $data['account_number'],
                'maya_account_name' => $data['account_name'],
            ]);
        }

        return redirect()->route($this->routePrefix().'index')->with('success', __('Payment method saved.'));
    }

    private function routePrefix(): string
    {
        return (string) str(request()->route()->getName())->beforeLast('.').'.';
    }
}
