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
            'routePrefix' => $this->routePrefix(),
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

        return redirect()->route($this->routePrefix().'show', $transaction);
    }

    public function storeWithdrawal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(PaybucksChannel::CHANNELS)],
            'account_number' => 'required|string|max:32',
            'account_name' => 'nullable|string|max:191',
        ]);

        try {
            $transaction = $this->cashService->createPaybucksWithdrawal(
                auth()->user(),
                $data['channel'],
                $data['account_number'],
                $data['account_name'] ?? null,
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
}
