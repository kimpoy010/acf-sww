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

    <div class="gold-panel p-4 mb-6">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5">
                <h2 class="font-semibold">{{ __('VIP Status') }}</h2>
                <button type="button" id="vip-info-open" aria-label="{{ __('About VIP levels') }}"
                        class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold leading-none shrink-0"
                        style="border:1px solid #9c8f7b;color:#9c8f7b">!</button>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full" style="background:linear-gradient(180deg,#f2d68e,#c9a04a 45%,#8a611a 100%);color:#241600">
                {{ $vipTier?->name ?? __('Not yet VIP') }}
            </span>
        </div>
        <p class="text-xs text-[#9c8f7b] mb-3">
            {{ __('Lifetime valid bets: :amount', ['amount' => $currencySymbol.number_format($lifetimeValidBets, 2)]) }}
            @if ($vipTier)
                &middot; {{ __('Rebate: :percent% on every settled bet', ['percent' => rtrim(rtrim(number_format($vipTier->rebate_percent, 2), '0'), '.')]) }}
            @endif
        </p>
        @if ($nextVipTier)
            @php
                $rangeStart = (float) ($vipTier->min_valid_bets ?? 0);
                $rangeEnd = (float) $nextVipTier->min_valid_bets;
                $progress = $rangeEnd > $rangeStart ? min(100, max(0, ($lifetimeValidBets - $rangeStart) / ($rangeEnd - $rangeStart) * 100)) : 0;
                $remaining = max(0, $rangeEnd - $lifetimeValidBets);
            @endphp
            <div class="h-2 rounded-full bg-[#141a2a] overflow-hidden mb-2">
                <div class="h-full rounded-full" style="width:{{ $progress }}%;background:linear-gradient(90deg,#8a611a,#c9a04a 60%,#f2d68e)"></div>
            </div>
            <p class="text-xs text-[#9c8f7b]">
                {{ __(':amount more in valid bets to reach :tier (:percent% rebate)', ['amount' => $currencySymbol.number_format($remaining, 2), 'tier' => $nextVipTier->name, 'percent' => rtrim(rtrim(number_format($nextVipTier->rebate_percent, 2), '0'), '.')]) }}
            </p>
        @else
            <p class="text-xs text-[#9c8f7b]">{{ __('You\'ve reached the highest VIP tier.') }}</p>
        @endif
    </div>

    <div id="vip-info-modal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm px-4">
        <div class="w-full sm:max-w-sm bg-[#0b0b0d] border rounded-t-2xl sm:rounded-2xl p-5 pb-6 max-h-[80vh] overflow-y-auto scroll-thin" style="border-color:rgba(168,121,31,.5)">
            <div class="flex items-center justify-between mb-4">
                <p class="font-semibold" style="color:#f4efe4">{{ __('VIP Levels') }}</p>
                <button type="button" id="vip-info-close" class="text-[#8a7a70] hover:text-white transition text-xl leading-none">&times;</button>
            </div>
            <p class="text-xs text-[#9c8f7b] mb-4">{{ __('Your VIP tier is based on your lifetime valid (matched) bets, and never resets. Each tier automatically credits its rebate percentage back to your wallet on every settled bet.') }}</p>
            <div class="space-y-2 text-sm">
                @foreach ($allVipTiers as $tier)
                    <div class="flex items-center justify-between rounded-lg px-3 py-2" style="background:#05070b;border:1px solid #141a2a">
                        <div>
                            <p class="font-semibold" style="color:#f4efe4">{{ $tier->name }}</p>
                            <p class="text-xs text-[#9c8f7b]">
                                {{ $currencySymbol }}{{ number_format($tier->min_valid_bets, 0) }}
                                @if ($tier->max_valid_bets)
                                    – {{ __('below') }} {{ $currencySymbol }}{{ number_format((float) $tier->max_valid_bets + 0.01, 0) }}
                                @else
                                    +
                                @endif
                            </p>
                        </div>
                        <span class="text-sm font-bold" style="color:#c9a04a">{{ rtrim(rtrim(number_format($tier->rebate_percent, 2), '0'), '.') }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

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

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('vip-info-modal');
        const openBtn = document.getElementById('vip-info-open');
        const closeBtn = document.getElementById('vip-info-close');
        if (!modal || !openBtn) return;

        const open = () => modal.classList.remove('hidden');
        const close = () => modal.classList.add('hidden');

        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    })();
</script>
@endpush
@endsection
