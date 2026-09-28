@extends('layouts.app')

@section('title', __('Downline Sub-Agents'))

@section('content')
<a href="{{ route('agent.dashboard') }}" class="text-sm text-[#9c8f7b] hover:text-[#c9a04a] transition">&larr; {{ __('Back to Dashboard') }}</a>

<h1 class="text-2xl font-bold mb-6 mt-2" style="color:#f4efe4">{{ __('Downline sub-agents (:count)', ['count' => $downlineAgents->total()]) }}</h1>

<div class="gold-panel divide-y divide-[#141a2a]">
    @forelse ($downlineAgents as $subAgent)
        <div class="flex items-center justify-between px-4 py-3 gap-4">
            <div class="min-w-0">
                <a href="{{ route('agent.downline.transactions', $subAgent) }}" class="hover:underline font-semibold block truncate" style="color:#c9a04a">{{ $subAgent->displayName() }}</a>
                <span class="text-[#8a7a70] text-xs">{{ __(':count downline', ['count' => $subAgent->downline_count]) }}</span>
            </div>
            <div class="text-right whitespace-nowrap">
                <p class="text-emerald-400 font-semibold">{{ $currencySymbol }}{{ number_format($subAgent->wallet->main_balance ?? 0, 2) }}</p>
                <p class="text-amber-400 text-xs">{{ __('Comm:') }} {{ $currencySymbol }}{{ number_format($subAgent->wallet->commission_balance ?? 0, 2) }}</p>
            </div>
        </div>
    @empty
        <p class="px-4 py-6 text-sm text-[#9c8f7b]">{{ __('No sub-agents yet.') }}</p>
    @endforelse
</div>

@if ($downlineAgents->hasPages())
    <div class="mt-4">
        {{ $downlineAgents->onEachSide(1)->links() }}
    </div>
@endif
@endsection
