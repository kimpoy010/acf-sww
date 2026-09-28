@extends('layouts.app')

@section('title', __('Payout settings'))

@section('content')
<h1 class="text-2xl font-bold mb-6" style="color:#f4efe4">{{ __('Payout settings') }}</h1>

<form method="POST" action="{{ route('superadmin.settings.update') }}" class="max-w-lg space-y-4">
    @csrf
    @method('PUT')

    <div class="gold-panel p-4">
        <label class="flex items-center justify-between gap-4 cursor-pointer">
            <span>
                <span class="block font-semibold">{{ __('Balancer switch') }}</span>
                <span class="block text-sm text-[#9c8f7b] mt-1">{{ __('When on, caps the combined meron+wala payout ratio so extremely lopsided pools don\'t produce extreme payouts on the favoured side.') }}</span>
            </span>
            <input type="checkbox" name="balancer_switch" value="1" @checked($balancerSwitch === 'on') class="w-5 h-5 rounded shrink-0">
        </label>
    </div>

    <div class="gold-panel p-4">
        <label class="block font-semibold mb-1">{{ __('Withdrawal fee') }}</label>
        <span class="block text-sm text-[#9c8f7b] mb-2">{{ __('Added on top of whatever a player/agent asks to withdraw — they still receive the amount they requested; this is deducted from their wallet in addition to it.') }}</span>
        <input type="text" inputmode="decimal" name="withdrawal_fee" value="{{ old('withdrawal_fee', $withdrawalFee) }}" required
               class="w-full max-w-xs gold-input px-3 py-2">
    </div>

    <button class="gold-btn">{{ __('Save') }}</button>
</form>
@endsection
