<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Services\CashTransactionService;
use App\Services\Paybucks\PaybucksChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared by both players (play.cash.*) and agents (agent.cash.*) — same
 * controller and views, reached under two different prefixes. routePrefix()
 * derives which one from the current route's own name so the views never
 * hardcode "play.cash." and break for an agent visiting agent.cash.*.
 */
class CashController extends Controller
{
    public function __construct(private CashTransactionService $cashService) {}

    public function index(): View
    {
        $wallet = auth()->user()->wallet;
        $pending = CashTransaction::where('user_id', auth()->id())->where('status', 'pending')->first();

        if ($pending && $pending->isExpired()) {
            $pending = $this->cashService->expire($pending);
        }

        $history = CashTransaction::where('user_id', auth()->id())
            ->whereNotIn('status', ['pending'])
            ->with('teller:id,name,username')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('player.cash.index', [
            'wallet' => $wallet,
            'pending' => $pending,
            'history' => $history,
            'player' => auth()->user(),
            'routePrefix' => $this->routePrefix(),
            'topRoutePrefix' => $this->topRoutePrefix(),
            'withdrawalFee' => CashTransactionService::withdrawalFee(),
        ]);
    }

    public function storeDeposit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(PaybucksChannel::CHANNELS)],
            'amount' => 'required|numeric|min:20',
            'account_number' => 'nullable|string|max:32',
        ]);

        try {
            $transaction = $this->cashService->createPaybucksDeposit(
                auth()->user(),
                (float) $data['amount'],
                $data['channel'],
                $data['account_number'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route($this->routePrefix().'index')->with('error', $e->getMessage());
        }

        // Tells the show page to open the payment modal immediately on
        // load, instead of requiring an extra tap on "Open payment page"
        // right after the player just asked to deposit. A flash value
        // rather than a query string so it only fires on this exact
        // redirect — reloading or revisiting the same page later (status
        // poll, browser refresh) won't keep reopening it.
        return redirect()->route($this->routePrefix().'show', $transaction)->with('open_payment_modal', true);
    }

    public function storeWithdrawal(Request $request): RedirectResponse
    {
        $player = auth()->user();

        // The wallet PIN is a player-only requirement — players set one on
        // their Profile page (see WalletPinController); agents share this
        // same action under agent.cash.* but have no such page, so they're
        // exempt rather than permanently locked out of withdrawing.
        $pinRequired = $this->topRoutePrefix() === 'play.';

        // Mandatory, not just checked when present — set it once before
        // any withdrawal can go through at all.
        if ($pinRequired && ! $player->hasWalletPin()) {
            return redirect()->route('play.profile')
                ->with('error', __('Set a withdrawal PIN first before you can withdraw.'));
        }

        $data = $request->validate([
            'channel' => ['required', Rule::in(PaybucksChannel::CHANNELS)],
            'amount' => 'required|numeric|min:20',
            'pin' => [$pinRequired ? 'required' : 'nullable', 'digits:4'],
        ]);

        if ($pinRequired && ! $player->checkWalletPin($data['pin'])) {
            return redirect()->route($this->routePrefix().'index')->with('error', __('Incorrect withdrawal PIN.'));
        }

        // Withdrawals are never sent wherever this request happens to
        // type in — only to the account the player registered ahead of
        // time on the Payment Methods page (see User::hasSavedPaymentMethod()).
        if (! $player->hasSavedPaymentMethod($data['channel'])) {
            return redirect()->route($this->topRoutePrefix().'payment-methods.index')
                ->with('error', __('Save your :channel account first before withdrawing to it.', ['channel' => PaybucksChannel::label($data['channel'])]));
        }

        try {
            $transaction = $this->cashService->createPaybucksWithdrawal(
                $player,
                $data['channel'],
                (float) $data['amount'],
                $player->savedAccountNumber($data['channel']),
                $player->savedAccountName($data['channel']),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route($this->routePrefix().'index')->with('error', $e->getMessage());
        }

        return redirect()->route($this->routePrefix().'show', $transaction);
    }

    public function show(CashTransaction $cashTransaction): View|RedirectResponse
    {
        abort_if($cashTransaction->user_id !== auth()->id(), 403);

        if ($cashTransaction->isExpired()) {
            $cashTransaction = $this->cashService->expire($cashTransaction);
        } elseif ($cashTransaction->provider === 'paybucks' && $cashTransaction->isPending()) {
            // Opportunistic re-check on every page load — a fallback in
            // case Paybucks' own callback was ever lost, on top of the
            // live broadcast/poll the page itself does afterward.
            $cashTransaction = $this->cashService->reconcilePaybucksOrder($cashTransaction);
        }

        return view('player.cash.show', [
            'cashTransaction' => $cashTransaction,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function status(CashTransaction $cashTransaction): JsonResponse
    {
        abort_if($cashTransaction->user_id !== auth()->id(), 403);

        if ($cashTransaction->isExpired()) {
            $cashTransaction = $this->cashService->expire($cashTransaction);
        } elseif ($cashTransaction->provider === 'paybucks' && $cashTransaction->isPending()) {
            $cashTransaction = $this->cashService->reconcilePaybucksOrder($cashTransaction);
        }

        return response()->json(['status' => $cashTransaction->status]);
    }

    public function cancel(CashTransaction $cashTransaction): RedirectResponse
    {
        abort_if($cashTransaction->user_id !== auth()->id(), 403);

        try {
            $this->cashService->cancel($cashTransaction);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route($this->routePrefix().'index')->with('error', $e->getMessage());
        }

        return redirect()->route($this->routePrefix().'index')->with('success', __('Request cancelled.'));
    }

    /**
     * "play.cash." or "agent.cash." — derived from the current route's own
     * name (e.g. "agent.cash.deposit") rather than the user's role, so it
     * always matches whichever URL prefix actually served this request.
     */
    private function routePrefix(): string
    {
        return (string) str(request()->route()->getName())->beforeLast('.').'.';
    }

    /**
     * "play." or "agent." — the top-level prefix, for linking out to a
     * sibling route group (payment-methods.*) rather than another
     * cash.* route.
     */
    private function topRoutePrefix(): string
    {
        return (string) str(request()->route()->getName())->before('.').'.';
    }
}
