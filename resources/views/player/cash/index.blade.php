@extends('layouts.app')

@section('title', __('Cash In / Cash Out'))

@php
    $PaybucksChannel = \App\Services\Paybucks\PaybucksChannel::class;

    $statusLabels = [
        'completed' => __('Approved'),
        'cancelled' => __('Cancelled'),
        'expired' => __('Expired'),
        'failed' => __('Failed'),
    ];
@endphp

@section('content')
<p class="text-[10.5px] font-extrabold tracking-[0.16em] text-[#e0793a] mb-1">{{ __('POOL SABONG') }}</p>
<div class="flex items-start justify-between gap-3 mb-6">
    <h1 class="text-2xl font-extrabold tracking-tight">{{ __('Cash In / Cash Out') }}</h1>
    <a href="{{ route($topRoutePrefix.'payment-methods.index') }}" class="text-xs text-[#8a7a70] hover:text-[#c9baaf] transition underline whitespace-nowrap mt-1.5">{{ __('Payment methods') }}</a>
</div>

<div class="relative rounded-2xl overflow-hidden p-6 mb-6 border border-red-900/25" style="background: radial-gradient(circle at 15% -10%, #141f7a, transparent 55%), linear-gradient(160deg, #0c111c, #05080e);">
    <p class="text-[10.5px] font-extrabold tracking-[0.1em] text-[#c99a7a]">{{ __('WALLET BALANCE') }}</p>
    <p class="text-4xl font-extrabold text-amber-400 mt-1.5" style="text-shadow: 0 0 24px rgba(251,191,36,0.25);">{{ $currencySymbol }}{{ number_format($wallet->main_balance ?? 0, 2) }}</p>
    @if (($wallet->pending_withdrawal ?? 0) >= 0.01)
        <p class="text-xs text-amber-400 mt-1">{{ __(':amount held for a pending withdrawal', ['amount' => $currencySymbol.number_format($wallet->pending_withdrawal, 2)]) }}</p>
    @endif
</div>

@if (session('error'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(120,20,20,0.25);border:1px solid rgba(220,38,38,0.5);">{{ session('error') }}</div>
@endif

@if ($pending)
    <div class="rounded-xl p-4 mb-6" style="background:rgba(120,53,15,0.25);border:1px solid rgba(217,119,6,0.5);">
        <p class="font-semibold mb-1">{{ __('You have a pending :type request', ['type' => __(ucfirst($pending->type))]) }}</p>
        <p class="text-sm text-[#c9baaf] mb-3">{{ __(':amount via :channel — waiting for confirmation.', ['amount' => $currencySymbol.number_format($pending->amount, 2), 'channel' => $pending->channel ? $PaybucksChannel::label($pending->channel) : __('cash')]) }}</p>
        <a href="{{ route($routePrefix.'show', $pending) }}" class="inline-block rounded-lg bg-red-600 hover:bg-red-500 transition font-semibold px-4 py-2 text-sm shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">{{ __('View request') }}</a>
    </div>
@else
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div id="cash-in" class="rounded-xl bg-[#0a0e16] border border-[#141a2a] p-4">
            <h2 class="font-semibold mb-3">{{ __('Cash in (deposit)') }}</h2>
            <form method="POST" action="{{ route($routePrefix.'deposit') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Channel') }}</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($PaybucksChannel::CHANNELS as $channel)
                            <label class="deposit-channel-option flex items-center justify-center gap-2 rounded-lg border border-[#141a2a] bg-[#05070b] py-2 cursor-pointer text-sm font-semibold has-[:checked]:border-red-500 has-[:checked]:bg-red-950/40">
                                <input type="radio" name="channel" value="{{ $channel }}" class="deposit-channel-input hidden" data-scope="deposit" data-requires-account="{{ $PaybucksChannel::depositRequiresAccountNumber($channel) ? '1' : '0' }}" {{ $loop->first ? 'checked' : '' }} required>
                                {{ $PaybucksChannel::label($channel) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Amount') }}</label>
                    <input type="text" inputmode="decimal" name="amount" required placeholder="{{ __('e.g. :amount', ['amount' => '500']) }}"
                           class="amount-input w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                <div id="deposit-account-number-field">
                    <label class="block text-sm text-[#8a7a70] mb-1">{{ __('GCash number') }}</label>
                    <input type="text" inputmode="numeric" name="account_number" placeholder="09XXXXXXXXX"
                           value="{{ old('account_number', $player->gcash_account_number) }}"
                           class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                    @unless ($player->gcash_account_number)
                        <p class="text-xs text-[#8a7a70] mt-1">{!! __('Tip: save this in :link so it fills in automatically next time.', ['link' => '<a href="'.route($topRoutePrefix.'payment-methods.index').'" class="underline">'.__('Payment methods').'</a>']) !!}</p>
                    @endunless
                </div>
                <button class="w-full rounded-lg bg-red-600 hover:bg-red-500 transition font-extrabold py-2 shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">{{ __('Deposit') }}</button>
            </form>
        </div>

        <div id="cash-out" class="rounded-xl bg-[#0a0e16] border border-[#141a2a] p-4">
            <h2 class="font-semibold mb-3">{{ __('Cash out (withdraw)') }}</h2>
            <p class="text-sm text-[#8a7a70] mb-3">{{ __('Withdraws your full available balance: :amount', ['amount' => $currencySymbol.number_format($wallet->availableBalance() ?? 0, 2)]) }}</p>
            <form method="POST" action="{{ route($routePrefix.'withdraw') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Channel') }}</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($PaybucksChannel::CHANNELS as $channel)
                            <label class="withdraw-channel-option flex items-center justify-center gap-2 rounded-lg border border-[#141a2a] bg-[#05070b] py-2 cursor-pointer text-sm font-semibold has-[:checked]:border-red-500 has-[:checked]:bg-red-950/40">
                                <input type="radio" name="channel" value="{{ $channel }}" class="withdraw-channel-input hidden" data-scope="withdraw" data-requires-account-name="{{ $PaybucksChannel::withdrawalRequiresAccountName($channel) ? '1' : '0' }}" {{ $loop->first ? 'checked' : '' }} required>
                                {{ $PaybucksChannel::label($channel) }}
                            </label>
                        @endforeach
                    </div>
                </div>
                @foreach ($PaybucksChannel::CHANNELS as $channel)
                    @php $hasSaved = $player->hasSavedPaymentMethod($channel); @endphp
                    <div id="withdraw-destination-{{ $channel }}" class="withdraw-destination {{ $loop->first ? '' : 'hidden' }}" data-has-account="{{ $hasSaved ? '1' : '0' }}">
                        @if ($hasSaved)
                            <div class="rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 text-sm">
                                <p class="text-[#8a7a70] text-xs mb-0.5">{{ __('Sending to') }}</p>
                                <p class="font-semibold">{{ $player->savedAccountNumber($channel) }}{{ $player->savedAccountName($channel) ? ' — '.$player->savedAccountName($channel) : '' }}</p>
                            </div>
                        @else
                            <div class="rounded-lg px-3 py-2 text-sm" style="background:rgba(120,53,15,0.2);border:1px solid rgba(217,119,6,0.4);">
                                {!! __('No :channel account saved yet — add one in :link first.', ['channel' => $PaybucksChannel::label($channel), 'link' => '<a href="'.route($topRoutePrefix.'payment-methods.index').'" class="underline font-semibold">'.__('Payment methods').'</a>']) !!}
                            </div>
                        @endif
                    </div>
                @endforeach
                <button id="withdraw-submit" type="submit" class="w-full rounded-lg bg-white/[0.06] border border-white/[0.14] hover:bg-white/[0.1] transition font-extrabold py-2"
                        data-no-balance="{{ ($wallet->availableBalance() ?? 0) < 0.01 ? '1' : '0' }}"
                        {{ ($wallet->availableBalance() ?? 0) < 0.01 || ! $player->hasSavedPaymentMethod($PaybucksChannel::CHANNELS[0]) ? 'disabled' : '' }}>
                    {{ __('Withdraw') }}
                </button>
            </form>
        </div>
    </div>
@endif

<div class="rounded-xl bg-[#0a0e16] border border-[#141a2a] p-4">
    <h2 class="font-semibold mb-1 px-1">{{ __('History') }}</h2>
    <div class="divide-y divide-[#141a2a]">
        @forelse ($history as $tx)
            @php
                $typeLabel = $tx->type === 'deposit' ? __('Deposit') : __('Withdrawal');
                $statusLabel = $statusLabels[$tx->status] ?? ucfirst($tx->status);
                $isDeposit = $tx->type === 'deposit';
                $isCompleted = $tx->status === 'completed';
                $channelLabel = $tx->channel ? $PaybucksChannel::label($tx->channel) : ($tx->teller?->username ?? $tx->teller?->name ?? '—');
            @endphp
            <button type="button"
                class="cash-tx-row w-full text-left px-1 py-3 hover:bg-white/[0.03] rounded-lg transition"
                data-title="{{ __(':type request', ['type' => $typeLabel]) }}"
                data-status="{{ $tx->status }}"
                data-status-label="{{ $statusLabel }}"
                data-type="{{ $tx->type }}"
                data-amount="{{ number_format($tx->amount, 2) }}"
                data-code="{{ $tx->code }}"
                data-channel="{{ $channelLabel }}"
                data-datetime="{{ $tx->updated_at->format('F j, Y \a\t g:i A') }}">
                <div class="flex items-center justify-between text-xs text-[#8a7a70]">
                    <span>{{ __(':status :type on', ['status' => $statusLabel, 'type' => $typeLabel]) }}</span>
                    <span>{{ $tx->updated_at->format('h:i A') }}</span>
                </div>
                <div class="flex items-center justify-between mt-1 gap-3">
                    <span class="font-semibold text-[#f5efe9] truncate">
                        {{ $typeLabel }}
                        <span class="text-xs font-normal px-2 py-0.5 rounded-full ml-1
                            {{ $isCompleted ? 'bg-emerald-700 text-emerald-100' : ($tx->status === 'cancelled' ? 'bg-[#141a2a] text-[#c9baaf]' : 'bg-red-800 text-red-100') }}">
                            {{ strtoupper($statusLabel) }}
                        </span>
                    </span>
                    <span class="font-semibold whitespace-nowrap {{ $isDeposit ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $isDeposit ? '+' : '-' }}{{ $currencySymbol }}{{ number_format($tx->amount, 2) }}
                    </span>
                </div>
            </button>
        @empty
            <p class="py-4 px-1 text-[#8a7a70] text-sm">{{ __('No cash transactions yet.') }}</p>
        @endforelse
    </div>
</div>

<!-- Transaction detail modal -->
<div id="cash-tx-modal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="w-full sm:max-w-sm bg-[#0a0e16] border border-[#141a2a] rounded-t-2xl sm:rounded-2xl p-5 pb-6">
        <div class="flex items-center justify-between mb-4">
            <p class="font-semibold text-[#f5efe9]">{{ __('Transaction details') }}</p>
            <button type="button" id="cash-tx-modal-close" class="text-[#8a7a70] hover:text-white transition text-xl leading-none">&times;</button>
        </div>

        <p class="text-center text-2xl font-extrabold mb-1" id="cash-tx-modal-amount"></p>
        <p class="text-center text-sm text-[#8a7a70] mb-5" id="cash-tx-modal-title"></p>

        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Date & time') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="cash-tx-modal-datetime"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Status') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="cash-tx-modal-status"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Reference code') }}</dt>
                <dd class="text-[#f5efe9] text-right font-mono" id="cash-tx-modal-code"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Channel') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="cash-tx-modal-channel"></dd>
            </div>
        </dl>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        // Toggle the GCash-only account-number field on the deposit form.
        const depositField = document.getElementById('deposit-account-number-field');
        document.querySelectorAll('.deposit-channel-input').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) depositField.classList.toggle('hidden', input.dataset.requiresAccount !== '1');
            });
        });
        const checkedDeposit = document.querySelector('.deposit-channel-input:checked');
        if (checkedDeposit) depositField.classList.toggle('hidden', checkedDeposit.dataset.requiresAccount !== '1');

        // Show the saved destination for whichever channel is selected, and
        // disable the submit button unless that channel has one saved.
        const withdrawSubmit = document.getElementById('withdraw-submit');
        const showWithdrawDestination = (channel) => {
            document.querySelectorAll('.withdraw-destination').forEach((el) => {
                el.classList.toggle('hidden', el.id !== 'withdraw-destination-' + channel);
            });
            const active = document.getElementById('withdraw-destination-' + channel);
            if (withdrawSubmit && active) {
                withdrawSubmit.disabled = active.dataset.hasAccount !== '1' || withdrawSubmit.dataset.noBalance === '1';
            }
        };
        document.querySelectorAll('.withdraw-channel-input').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) showWithdrawDestination(input.value);
            });
        });
        const checkedWithdraw = document.querySelector('.withdraw-channel-input:checked');
        if (checkedWithdraw) showWithdrawDestination(checkedWithdraw.value);
    })();

    (function () {
        const modal = document.getElementById('cash-tx-modal');
        if (!modal) return;

        const amountEl = document.getElementById('cash-tx-modal-amount');
        const titleEl = document.getElementById('cash-tx-modal-title');
        const datetimeEl = document.getElementById('cash-tx-modal-datetime');
        const statusEl = document.getElementById('cash-tx-modal-status');
        const codeEl = document.getElementById('cash-tx-modal-code');
        const channelEl = document.getElementById('cash-tx-modal-channel');
        const closeBtn = document.getElementById('cash-tx-modal-close');

        const open = (row) => {
            const isDeposit = row.dataset.type === 'deposit';
            amountEl.textContent = (isDeposit ? '+' : '-') + @json($currencySymbol) + row.dataset.amount;
            amountEl.className = 'text-center text-2xl font-extrabold mb-1 ' + (isDeposit ? 'text-emerald-400' : 'text-red-400');
            titleEl.textContent = row.dataset.title;
            datetimeEl.textContent = row.dataset.datetime;
            statusEl.textContent = row.dataset.statusLabel;
            codeEl.textContent = row.dataset.code;
            channelEl.textContent = row.dataset.channel;
            modal.classList.remove('hidden');
        };

        const close = () => modal.classList.add('hidden');

        document.querySelectorAll('.cash-tx-row').forEach((row) => {
            row.addEventListener('click', () => open(row));
        });

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
