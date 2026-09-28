@extends('layouts.app')

@section('title', __('Payment Methods'))

@section('content')
<p class="text-[10.5px] font-extrabold tracking-[0.16em] text-[#e0793a] mb-1">{{ __('POOL SABONG') }}</p>
<h1 class="text-2xl font-extrabold tracking-tight mb-2">{{ __('Payment Methods') }}</h1>
<p class="text-sm text-[#8a7a70] mb-6">{{ __('Save your GCash and/or Maya account once — deposits will use it automatically, and withdrawals will only ever be sent here.') }}</p>

@if (session('success'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(6,78,59,0.25);border:1px solid rgba(16,185,129,0.4);">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(120,20,20,0.25);border:1px solid rgba(220,38,38,0.5);">{{ session('error') }}</div>
@endif

<div class="grid md:grid-cols-2 gap-6">
    <div class="rounded-xl bg-[#0a0e16] border border-[#141a2a] p-4">
        <h2 class="font-semibold mb-3">{{ __('GCash') }}</h2>
        <form method="POST" action="{{ route($routePrefix.'update') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="channel" value="gcash">
            <div>
                <label class="block text-sm text-[#8a7a70] mb-1">{{ __('GCash number') }}</label>
                <input type="text" inputmode="numeric" name="account_number" required placeholder="09XXXXXXXXX"
                       value="{{ old('account_number', $player->gcash_account_number) }}"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <button class="w-full rounded-lg bg-red-600 hover:bg-red-500 transition font-extrabold py-2 shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">
                {{ $player->gcash_account_number ? __('Update') : __('Save') }}
            </button>
        </form>
    </div>

    <div class="rounded-xl bg-[#0a0e16] border border-[#141a2a] p-4">
        <h2 class="font-semibold mb-3">{{ __('Maya') }}</h2>
        <form method="POST" action="{{ route($routePrefix.'update') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="channel" value="maya">
            <div>
                <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Maya number') }}</label>
                <input type="text" inputmode="numeric" name="account_number" required placeholder="09XXXXXXXXX"
                       value="{{ old('account_number', $player->maya_account_number) }}"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Account name') }}</label>
                <input type="text" name="account_name" required placeholder="{{ __('Full name on the account') }}"
                       value="{{ old('account_name', $player->maya_account_name) }}"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <button class="w-full rounded-lg bg-white/[0.06] border border-white/[0.14] hover:bg-white/[0.1] transition font-extrabold py-2">
                {{ $player->maya_account_number ? __('Update') : __('Save') }}
            </button>
        </form>
    </div>
</div>
@endsection
