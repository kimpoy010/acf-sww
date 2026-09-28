<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Event;
use App\Models\User;
use App\Support\WalletTransactionEventNames;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class DownlineController extends Controller
{
    /**
     * Which reference_types each tab shows — same convention as
     * Superadmin\WalletController. Downline agents don't place bets, so
     * their ledger only ever gets an 'all' / 'deposits' / 'withdrawals'
     * tab set; players additionally get 'bets'.
     */
    private const PLAYER_TAB_REFERENCE_TYPES = [
        'bets' => WalletTransactionEventNames::BET_LINKED_REFERENCE_TYPES,
        'deposits' => ['deposit', 'admin_topup'],
        'withdrawals' => ['withdrawal', 'admin_withdraw'],
    ];

    private const AGENT_TAB_REFERENCE_TYPES = [
        'deposits' => ['deposit', 'admin_topup'],
        'withdrawals' => ['withdrawal', 'admin_withdraw'],
    ];

    /**
     * A downline account's wallet ledger, viewed by the recruiting agent —
     * scoped to their own direct downline only (one level deep, same as
     * the dashboard's own downline lists), never a sub-agent's downline.
     */
    public function transactions(Request $request, User $user): View|Response
    {
        abort_unless($user->agent_id === auth()->id(), 403);

        $isPlayer = $user->hasRole('player');
        $allowedTabs = $isPlayer ? ['all', 'bets', 'deposits', 'withdrawals'] : ['all', 'deposits', 'withdrawals'];
        $tabTypes = $isPlayer ? self::PLAYER_TAB_REFERENCE_TYPES : self::AGENT_TAB_REFERENCE_TYPES;

        $data = $request->validate([
            'tab' => ['nullable', Rule::in($allowedTabs)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        $tab = $data['tab'] ?? 'all';
        $eventId = ($isPlayer && $tab === 'bets') ? ($data['event_id'] ?? null) : null;

        $wallet = $user->wallet;

        $transactions = $wallet
            ? $wallet->transactions()
                ->when($tab !== 'all', fn ($q) => $q->whereIn('reference_type', $tabTypes[$tab]))
                ->when($data['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                ->when($data['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
                ->when($eventId, function ($q) use ($eventId) {
                    $betIds = Bet::whereHas('fight', fn ($fq) => $fq->where('event_id', $eventId))->pluck('id');
                    $q->whereIn('reference_id', $betIds);
                })
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString()
            : null;

        $eventNamesByBetId = $transactions
            ? WalletTransactionEventNames::forTransactions($transactions->getCollection())
            : collect();

        // Only offered on the Bets tab (players only), but cheap enough to
        // just always compute when relevant.
        $playerEvents = $isPlayer
            ? Event::whereHas('fights.bets', fn ($q) => $q->where('user_id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        if ($request->ajax()) {
            return response()->view('agent.downline._transactions-rows', compact('transactions', 'eventNamesByBetId'));
        }

        return view('agent.downline.transactions', [
            'targetUser' => $user,
            'isPlayer' => $isPlayer,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'eventNamesByBetId' => $eventNamesByBetId,
            'tab' => $tab,
            'dateFrom' => $data['date_from'] ?? null,
            'dateTo' => $data['date_to'] ?? null,
            'eventId' => $eventId,
            'playerEvents' => $playerEvents,
        ]);
    }
}
