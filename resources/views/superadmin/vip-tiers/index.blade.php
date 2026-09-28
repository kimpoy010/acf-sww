@extends('layouts.app')

@section('title', __('VIP Tiers'))

@section('content')
<h1 class="text-2xl font-bold mb-6" style="color:#f4efe4">{{ __('VIP Tiers') }}</h1>

<p class="text-sm text-[#9c8f7b] mb-6 max-w-lg">
    {{ __('Rebate tiers based on a player\'s lifetime matched wagering. A player is auto-ranked into the highest tier their total valid bets clears, and earns that tier\'s rebate percentage back on every settled meron/wala bet — credited automatically at settlement, no manual payout needed.') }}
</p>

<div class="space-y-3 max-w-3xl mb-6">
    <div class="grid grid-cols-[1fr_1fr_1fr_100px] gap-3 px-4 text-xs text-[#9c8f7b] uppercase tracking-wide">
        <span>{{ __('Name') }}</span>
        <span>{{ __('Min valid bets') }}</span>
        <span>{{ __('Max valid bets') }}</span>
        <span>{{ __('Rebate %') }}</span>
    </div>
    @forelse ($vipTiers as $tier)
        <div class="gold-panel p-4">
            <form method="POST" action="{{ route('superadmin.vip-tiers.update', $tier) }}" class="grid grid-cols-[1fr_1fr_1fr_100px] gap-3 items-center">
                @csrf
                @method('PUT')
                <input name="name" value="{{ old('name', $tier->name) }}" required class="gold-input rounded px-2 py-1.5 text-sm">
                <input type="number" step="0.01" min="0" name="min_valid_bets" value="{{ old('min_valid_bets', $tier->min_valid_bets) }}" required class="gold-input rounded px-2 py-1.5 text-sm">
                <input type="number" step="0.01" min="0" name="max_valid_bets" value="{{ old('max_valid_bets', $tier->max_valid_bets) }}" placeholder="{{ __('No cap') }}" class="gold-input rounded px-2 py-1.5 text-sm">
                <input type="number" step="0.01" min="0" max="100" name="rebate_percent" value="{{ old('rebate_percent', $tier->rebate_percent) }}" required class="gold-input rounded px-2 py-1.5 text-sm">
                <div class="col-span-4 flex items-center justify-end gap-3 mt-1">
                    <button class="gold-btn gold-btn-sm">{{ __('Save') }}</button>
                </div>
            </form>
            <form method="POST" action="{{ route('superadmin.vip-tiers.destroy', $tier) }}" onsubmit="return confirm('{{ __('Delete :name?', ['name' => $tier->name]) }}')" class="text-right mt-1">
                @csrf
                @method('DELETE')
                <button class="text-xs text-red-400 hover:underline">{{ __('Delete') }}</button>
            </form>
        </div>
    @empty
        <p class="px-4 py-6 text-sm text-[#9c8f7b]">{{ __('No VIP tiers yet.') }}</p>
    @endforelse
</div>

<form method="POST" action="{{ route('superadmin.vip-tiers.store') }}" class="max-w-3xl gold-panel p-4 flex flex-wrap items-end gap-3">
    @csrf
    <div>
        <label class="block text-xs text-[#9c8f7b] mb-1">{{ __('Name') }}</label>
        <input name="name" required placeholder="{{ __('e.g. VIP 4') }}" class="w-28 gold-input rounded px-2 py-1.5 text-sm">
    </div>
    <div>
        <label class="block text-xs text-[#9c8f7b] mb-1">{{ __('Min valid bets') }}</label>
        <input type="number" step="0.01" min="0" name="min_valid_bets" required class="w-32 gold-input rounded px-2 py-1.5 text-sm">
    </div>
    <div>
        <label class="block text-xs text-[#9c8f7b] mb-1">{{ __('Max valid bets') }}</label>
        <input type="number" step="0.01" min="0" name="max_valid_bets" placeholder="{{ __('No cap') }}" class="w-32 gold-input rounded px-2 py-1.5 text-sm">
    </div>
    <div>
        <label class="block text-xs text-[#9c8f7b] mb-1">{{ __('Rebate %') }}</label>
        <input type="number" step="0.01" min="0" max="100" name="rebate_percent" required class="w-20 gold-input rounded px-2 py-1.5 text-sm">
    </div>
    <button class="gold-btn-outline">{{ __('Add tier') }}</button>
</form>
@endsection
