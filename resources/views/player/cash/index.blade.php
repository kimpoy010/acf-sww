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

<div class="flex items-start justify-between gap-3 mb-4">
    <div>
        <p class="gold-eyebrow" style="text-align:left">{{ $cms['site_name'] }}</p>
        <h1 class="gold-serif" style="font-size:24px;font-weight:600;color:#f4efe4;margin-top:2px">{{ __('Cash In / Cash Out') }}</h1>
    </div>
    <a href="{{ route($topRoutePrefix.'payment-methods.index') }}" class="gold-chip-btn whitespace-nowrap mt-1">{{ __('Payment methods') }}</a>
</div>

<div class="gold-card mb-4">
    <p class="gold-label">{{ __('Wallet Balance') }}</p>
    <p class="gold-balance" style="margin-bottom:0">{{ $currencySymbol }}{{ number_format($wallet->main_balance ?? 0, 2) }}</p>
    @if (($wallet->pending_withdrawal ?? 0) >= 0.01)
        <p class="text-xs text-center mt-2" style="color:#c9a04a">{{ __(':amount held for a pending withdrawal', ['amount' => $currencySymbol.number_format($wallet->pending_withdrawal, 2)]) }}</p>
    @endif
</div>

@if (session('error'))
    <div class="rounded-xl p-4 mb-6 text-sm" style="background:rgba(120,20,20,0.25);border:1px solid rgba(220,38,38,0.5);">{{ session('error') }}</div>
@endif

@if ($pending)
    <div class="mb-6">
        @include('player.partials.cash-pending')
    </div>
@else
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        @include('player.partials.deposit-form')
        @include('player.partials.withdraw-form')
    </div>
@endif

<div class="gold-panel p-4">
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
    <div class="w-full sm:max-w-sm bg-[#0b0b0d] border rounded-t-2xl sm:rounded-2xl p-5 pb-6" style="border-color:rgba(168,121,31,.5)">
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
@include('player.partials.cash-forms-scripts')
<script>
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
