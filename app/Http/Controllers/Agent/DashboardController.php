<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\CommissionLog;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): View
    {
        $agent = auth()->user();

        // Top 10 by wallet balance, highest first — the dashboard is a
        // glanceable summary, not the full roster (see DownlineController's
        // players()/agents() for the full, paginated "View All" lists,
        // same balance-descending order just not capped at 10).
        $downlineAgentsCount = $agent->downline()->role('agent')->count();
        $downlineAgents = $agent->downline()->role('agent')
            ->join('wallets', 'wallets.user_id', '=', 'users.id')
            ->select('users.*')
            ->with('wallet')
            ->withCount('downline')
            ->orderByDesc('wallets.main_balance')
            ->limit(10)
            ->get();

        $downlinePlayersCount = $agent->downline()->role('player')->count();
        $downlinePlayers = $agent->downline()->role('player')
            ->join('wallets', 'wallets.user_id', '=', 'users.id')
            ->select('users.*')
            ->with('wallet')
            ->orderByDesc('wallets.main_balance')
            ->limit(10)
            ->get();

        $logs = CommissionLog::where('agent_id', $agent->id)
            ->with('player:id,name,username')
            ->orderByDesc('credited_at')
            ->limit(25)
            ->get();

        $totalEarned = CommissionLog::where('agent_id', $agent->id)->sum('amount');
        $thisMonth = CommissionLog::where('agent_id', $agent->id)
            ->whereMonth('credited_at', now()->month)
            ->whereYear('credited_at', now()->year)
            ->sum('amount');

        $referralUrl = $agent->referral_code ? route('register', ['ref' => $agent->referral_code]) : null;

        return view('agent.dashboard', compact(
            'agent', 'downlineAgents', 'downlineAgentsCount', 'downlinePlayers', 'downlinePlayersCount',
            'logs', 'totalEarned', 'thisMonth', 'referralUrl'
        ));
    }

    public function transfer(WalletService $walletService): RedirectResponse
    {
        try {
            $amount = $walletService->transferCommissionToMain(auth()->user()->wallet);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Transferred :amount to your main balance.', ['amount' => '₱'.number_format($amount, 2)]));
    }
}
