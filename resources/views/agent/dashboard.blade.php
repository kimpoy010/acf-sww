@extends('layouts.app')

@section('title', __('Agent Dashboard'))

@section('content')

<div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
<p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
<h1 class="gold-serif gold-h1">{{ __('Agent Dashboard') }}</h1>

<div class="grid md:grid-cols-3 gap-4 mb-8">
    <div class="gold-panel p-4">
        <p class="text-xs uppercase text-[#9c8f7b] mb-1">{{ __('Main balance') }}</p>
        <p class="text-2xl font-bold text-emerald-400">{{ $currencySymbol }}{{ number_format($agent->wallet->main_balance ?? 0, 2) }}</p>
        <div class="flex flex-wrap gap-2 mt-2">
            <a href="{{ route('agent.cash.index') }}" class="gold-chip-btn">{{ __('Cash In / Out') }}</a>
            <a href="{{ route('agent.payment-methods.index') }}" class="gold-chip-btn">{{ __('Payment methods') }}</a>
        </div>
    </div>
    <div class="gold-panel p-4">
        <p class="text-xs uppercase text-[#9c8f7b] mb-1">{{ __('Commission balance') }}</p>
        <p class="text-2xl font-bold text-amber-400">{{ $currencySymbol }}{{ number_format($agent->wallet->commission_balance ?? 0, 2) }}</p>
        @if (($agent->wallet->commission_balance ?? 0) >= 0.01)
            <form method="POST" action="{{ route('agent.transfer') }}" class="mt-2">
                @csrf
                <button type="submit" class="gold-chip-btn">{{ __('Transfer to main balance') }}</button>
            </form>
        @endif
    </div>
    <div class="gold-panel p-4">
        <p class="text-xs uppercase text-[#9c8f7b] mb-1">{{ __('Earned this month') }}</p>
        <p class="text-2xl font-bold" style="color:#f4efe4">{{ $currencySymbol }}{{ number_format($thisMonth, 2) }}</p>
        <p class="text-xs text-[#9c8f7b] mt-1">{{ __('All-time: :amount', ['amount' => $currencySymbol.number_format($totalEarned, 2)]) }}</p>
    </div>
</div>

@if ($agent->agentLevel)
    <p class="text-sm text-[#9c8f7b] mb-6">{{ __('Level:') }} <span class="font-semibold" style="color:#f4efe4">{{ $agent->agentLevel->label }}</span></p>
@else
    <p class="text-sm text-amber-400 mb-6">{{ __('No agent level assigned yet — ask a superadmin to set your level and commission rate.') }}</p>
@endif

@if ($referralUrl)
    <div class="gold-panel p-4 mb-8">
        <h2 class="font-semibold mb-2">{{ __('Your referral link') }}</h2>
        <p class="text-sm text-[#9c8f7b] mb-2">{{ __('Share this link to recruit players directly under you.') }}</p>
        <code class="block text-xs bg-[#05070b] rounded-lg px-3 py-2 break-all">{{ $referralUrl }}</code>
    </div>
@endif

<div class="grid lg:grid-cols-2 gap-6">
    <div class="gold-panel p-4">
        <h2 class="font-semibold mb-3">{{ __('Downline players (:count)', ['count' => $downlinePlayers->total()]) }}</h2>
        <div class="space-y-1 text-sm max-h-64 overflow-y-auto scroll-thin">
            @forelse ($downlinePlayers as $p)
                <div class="flex justify-between border-b border-[#141a2a] py-1">
                    <span>{{ $p->displayName() }}</span>
                </div>
            @empty
                <p class="text-[#8a7a70]">{{ __('No players recruited yet.') }}</p>
            @endforelse
        </div>
        @if ($downlinePlayers->hasPages())
            <div class="mt-2">
                {{ $downlinePlayers->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="gold-panel p-4">
        <h2 class="font-semibold mb-3">{{ __('Downline sub-agents (:count)', ['count' => $downlineAgents->count()]) }}</h2>
        <div class="space-y-1 text-sm max-h-64 overflow-y-auto scroll-thin">
            @forelse ($downlineAgents as $a)
                <div class="flex justify-between border-b border-[#141a2a] py-1">
                    <span>{{ $a->displayName() }}</span>
                    <span class="text-[#8a7a70]">{{ __(':count downline', ['count' => $a->downline_count]) }}</span>
                </div>
            @empty
                <p class="text-[#8a7a70]">{{ __('No sub-agents yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>

<div class="gold-panel p-4 mt-6">
    <h2 class="font-semibold mb-3">{{ __('Recent commission') }}</h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[#8a7a70] border-b border-[#141a2a]">
                <th class="py-1">{{ __('Player') }}</th>
                <th>{{ __('Side') }}</th>
                <th>{{ __('Bet amount') }}</th>
                <th>{{ __('Commission') }}</th>
                <th>{{ __('Date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="border-b border-[#141a2a]">
                    <td class="py-1">{{ $log->player?->displayName() ?? '—' }}</td>
                    <td class="capitalize">{{ $log->side }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($log->matched_amount ?? 0, 2) }}</td>
                    <td class="text-amber-400">{{ $currencySymbol }}{{ number_format($log->amount, 2) }}</td>
                    <td class="text-[#8a7a70]">{{ $log->credited_at->format('M j, H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-3 text-[#8a7a70]">{{ __('No commission earned yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
