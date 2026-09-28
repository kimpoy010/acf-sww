@extends('layouts.app')

@section('title', __('Downline Players'))

@section('content')
<a href="{{ route('agent.dashboard') }}" class="text-sm text-[#9c8f7b] hover:text-[#c9a04a] transition">&larr; {{ __('Back to Dashboard') }}</a>

<h1 class="text-2xl font-bold mb-6 mt-2" style="color:#f4efe4">{{ __('Downline players (:count)', ['count' => $downlinePlayers->total()]) }}</h1>

<div class="gold-panel divide-y divide-[#141a2a]">
    @forelse ($downlinePlayers as $player)
        <div class="flex items-center justify-between px-4 py-3 gap-4">
            <a href="{{ route('agent.downline.transactions', $player) }}" class="hover:underline font-semibold" style="color:#c9a04a">{{ $player->displayName() }}</a>
            <span class="text-emerald-400 font-semibold whitespace-nowrap">{{ $currencySymbol }}{{ number_format($player->wallet->main_balance ?? 0, 2) }}</span>
        </div>
    @empty
        <p class="px-4 py-6 text-sm text-[#9c8f7b]">{{ __('No players recruited yet.') }}</p>
    @endforelse
</div>

@if ($downlinePlayers->hasPages())
    <div class="mt-4">
        {{ $downlinePlayers->onEachSide(1)->links() }}
    </div>
@endif
@endsection
