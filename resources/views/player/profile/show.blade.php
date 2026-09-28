@extends('layouts.app')

@section('title', __('Profile'))

@php
    $routePrefix = 'play.payment-methods.';
@endphp

@section('content')

<div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
<p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
<h1 class="gold-serif gold-h1">{{ __('Profile') }}</h1>

<div class="max-w-sm mx-auto">
    <div class="gold-card mb-6" style="padding:16px 20px">
        <div class="flex items-center gap-3">
            <span class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold uppercase shrink-0"
                  style="background:linear-gradient(180deg,#f2d68e,#c9a04a 45%,#8a611a 100%);color:#241600">
                {{ Str::substr($player->displayName(), 0, 1) }}
            </span>
            <div class="min-w-0">
                <h2 class="text-xl font-bold truncate" style="color:#f4efe4">{{ $player->displayName() }}</h2>
                <p class="text-xs text-[#9c8f7b]">{{ __('Player') }}</p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl p-3 mb-4 text-sm" style="background:rgba(6,78,59,0.25);border:1px solid rgba(16,185,129,0.4);">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl p-3 mb-4 text-sm" style="background:rgba(120,20,20,0.25);border:1px solid rgba(220,38,38,0.5);">{{ session('error') }}</div>
    @endif

    <a href="{{ route('account.password.edit') }}" class="gold-btn-frame gold-btn-secondary mb-6">
        <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
        <span class="gold-btn-fill">{{ __('Change password') }}</span>
    </a>

    <div class="gold-panel p-4 mb-6">
        <h2 class="font-semibold mb-1">{{ __('Withdrawal PIN') }}</h2>
        <p class="text-xs text-[#9c8f7b] mb-3">
            @if ($player->hasWalletPin())
                {{ __('Required each time you withdraw. Enter a new one below to change it.') }}
            @else
                {{ __('Set a 4-digit PIN — required before you can withdraw from your wallet.') }}
            @endif
        </p>
        <form method="POST" action="{{ route('play.wallet-pin.update') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('New PIN') }}</label>
                <input type="password" inputmode="numeric" pattern="[0-9]*" maxlength="4" name="pin" required autocomplete="off"
                       placeholder="••••"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
            </div>
            <div>
                <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Confirm PIN') }}</label>
                <input type="password" inputmode="numeric" pattern="[0-9]*" maxlength="4" name="pin_confirmation" required autocomplete="off"
                       placeholder="••••"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
            </div>
            <button type="submit" class="gold-btn-frame gold-btn-primary">
                <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
                <span class="gold-btn-fill">{{ $player->hasWalletPin() ? __('Update PIN') : __('Set PIN') }}</span>
            </button>
        </form>
    </div>

    <div class="mb-6">
        <h2 class="font-semibold mb-1">{{ __('Payment Methods') }}</h2>
        <p class="text-xs text-[#9c8f7b] mb-3">{{ __('Save your GCash and/or Maya account once — deposits will use it automatically, and withdrawals will only ever be sent here.') }}</p>
        @include('player.partials.payment-method-forms')
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="gold-btn-frame gold-btn-secondary">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Logout') }}</span>
        </button>
    </form>
</div>
@endsection
