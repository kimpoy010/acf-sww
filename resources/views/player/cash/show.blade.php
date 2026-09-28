@extends('layouts.app')

@php
    $typeLabel = $cashTransaction->type === 'deposit' ? __('Deposit') : __('Withdrawal');
    $channelLabel = $cashTransaction->channel ? \App\Services\Paybucks\PaybucksChannel::label($cashTransaction->channel) : null;
@endphp

@section('title', __(':type QR', ['type' => $typeLabel]))

@section('content')
@include('player.partials.gold-theme-styles')

<div class="max-w-md mx-auto text-center">
    <p class="gold-eyebrow">{{ __('Pool Sabong') }}</p>
    <h1 class="gold-serif" style="font-size:20px;font-weight:600;color:#f4efe4;margin-bottom:4px">{{ __(':type request', ['type' => $typeLabel]) }}{{ $channelLabel ? ' · '.$channelLabel : '' }}</h1>
    <p class="gold-balance" style="font-size:30px;margin-bottom:18px">{{ $currencySymbol }}{{ number_format($cashTransaction->amount, 2) }}</p>

    <div id="pending-block" class="{{ $cashTransaction->status === 'pending' ? '' : 'hidden' }}">
        @if ($cashTransaction->type === 'deposit')
            @if ($cashTransaction->qr_image_url)
                <div class="bg-white rounded-xl p-4 inline-block mb-4">
                    <img src="{{ $cashTransaction->qr_image_url }}" alt="{{ __('Payment QR code') }}" class="w-48 h-48">
                </div>
                <p class="text-sm text-[#c9baaf] mb-1">{{ __('Scan with your :channel app to pay.', ['channel' => $channelLabel ?? __('GCash/Maya')]) }}</p>
            @elseif ($cashTransaction->qr_payload)
                {{-- Only ever a real QR Ph/InstaPay payload Paybucks itself
                     gave us — never generated from payment_url, which is
                     just a webpage link and isn't a format GCash/Maya's
                     scanner recognizes as a valid payment QR. --}}
                <div class="bg-white rounded-xl p-4 inline-block mb-4">
                    {!! \App\Support\QrCodeGenerator::svg($cashTransaction->qr_payload) !!}
                </div>
                <p class="text-sm text-[#c9baaf] mb-1">{{ __('Scan with your :channel app to pay.', ['channel' => $channelLabel ?? __('GCash/Maya')]) }}</p>
            @elseif ($cashTransaction->payment_url)
                <p class="mb-4">
                    <button type="button" id="open-payment-modal" class="gold-btn-frame gold-btn-primary inline is-active">
                        <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
                        <span class="gold-btn-fill">{{ __('Open payment page') }}</span>
                    </button>
                </p>
                <p class="text-sm text-[#c9baaf] mb-1">{{ __('Tap the button above to pay.') }}</p>
            @else
                <div class="rounded-xl px-4 py-6 mb-4" style="background:rgba(120,53,15,0.2);border:1px solid rgba(217,119,6,0.4);">
                    <p class="font-semibold mb-1">{{ __("We couldn't generate a way to pay for this request.") }}</p>
                    <p class="text-sm text-[#c9baaf]">{{ __('Please cancel it below and try again. If this keeps happening, contact support.') }}</p>
                </div>
            @endif
        @else
            <div class="gold-panel px-4 py-6 mb-4">
                <p class="text-[#c9baaf] font-semibold">{{ __('Sending your withdrawal to :channel…', ['channel' => $channelLabel ?? __('your account')]) }}</p>
                <p class="text-sm text-[#8a7a70] mt-1">{{ __('This page updates automatically once it settles.') }}</p>
                @if (($cashTransaction->platform_fee ?? 0) > 0)
                    <p class="text-xs text-[#8a7a70] mt-2">{{ __(':fee fee + :amount withdrawn = :total deducted from your wallet.', ['fee' => $currencySymbol.number_format($cashTransaction->platform_fee, 2), 'amount' => $currencySymbol.number_format($cashTransaction->amount, 2), 'total' => $currencySymbol.number_format($cashTransaction->amount + $cashTransaction->platform_fee, 2)]) }}</p>
                @endif
            </div>
        @endif

        <p class="text-xs text-[#8a7a70] mb-6">{{ __('Code:') }} <span class="font-mono">{{ $cashTransaction->code }}</span></p>

        @if ($cashTransaction->type === 'deposit')
            <form method="POST" action="{{ route($routePrefix.'cancel', $cashTransaction) }}">
                @csrf
                <button class="text-sm text-red-400 hover:underline">{{ __('Cancel this request') }}</button>
            </form>
        @endif
    </div>

    <div id="completed-block" class="{{ $cashTransaction->status === 'completed' ? '' : 'hidden' }}">
        <div class="rounded-xl px-4 py-6 mb-4" style="background:rgba(6,78,59,0.35);border:1px solid rgba(16,185,129,0.4);">
            <p class="text-emerald-300 font-semibold text-lg">✅ {{ __(':type completed', ['type' => $typeLabel]) }}</p>
        </div>
        <a href="{{ route($routePrefix.'index') }}" class="gold-btn-frame gold-btn-primary inline is-active">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Back to Cash In / Out') }}</span>
        </a>
    </div>

    <div id="cancelled-block" class="{{ in_array($cashTransaction->status, ['cancelled', 'expired', 'failed']) ? '' : 'hidden' }}">
        <div class="gold-panel px-4 py-6 mb-4">
            <p class="text-[#c9baaf] font-semibold text-lg">
                @if ($cashTransaction->status === 'failed')
                    {{ __('This request failed.') }} {{ $cashTransaction->provider_error_msg }}
                @else
                    {{ __('This request was :status.', ['status' => __($cashTransaction->status === 'expired' ? 'expired' : 'cancelled')]) }}
                @endif
            </p>
        </div>
        <a href="{{ route($routePrefix.'index') }}" class="gold-btn-frame gold-btn-primary inline is-active">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Back to Cash In / Out') }}</span>
        </a>
    </div>
</div>

@if ($cashTransaction->payment_url)
    {{-- Keeps the player on this page (so the live status update/poll
         above keeps running) instead of navigating away to pay. Some
         payment pages refuse to be framed at all (X-Frame-Options/CSP) —
         the "Open in new tab" escape hatch covers that; a fully blocked
         iframe just renders blank rather than erroring in a way JS can
         detect, so there's no automatic fallback beyond that link. --}}
    <div id="payment-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-3 sm:p-6">
        <div class="w-full h-full sm:max-w-lg sm:h-[85vh] bg-[#0a0e16] border rounded-xl flex flex-col overflow-hidden" style="border-color:rgba(168,121,31,.22)">
            <div class="flex items-center justify-between px-4 py-3 border-b shrink-0" style="border-color:rgba(168,121,31,.22)">
                <p class="font-semibold text-sm text-[#f5efe9]">{{ __('Complete your payment') }}</p>
                <div class="flex items-center gap-3">
                    <a href="{{ $cashTransaction->payment_url }}" target="_blank" rel="noopener" class="text-xs text-[#8a7a70] hover:text-[#c9baaf] transition whitespace-nowrap">{{ __('Open in new tab ↗') }}</a>
                    <button type="button" id="close-payment-modal" class="text-[#8a7a70] hover:text-white transition text-xl leading-none">&times;</button>
                </div>
            </div>
            <iframe id="payment-modal-iframe" src="about:blank" data-src="{{ $cashTransaction->payment_url }}" class="flex-1 w-full bg-white" title="{{ __('Payment page') }}"></iframe>
        </div>
    </div>
@endif

@push('scripts')
<script>
(function () {
    const initialStatus = @json($cashTransaction->status);
    if (initialStatus !== 'pending') {
        // Already resolved (completed/cancelled/expired/failed) — nothing
        // to watch. Wiring up onEchoReconnect() unconditionally here was
        // the bug: it fires on every fresh socket connection, INCLUDING
        // the very first one on this exact page load (see its own doc
        // comment in bootstrap.js), so poll() would immediately see this
        // same already-resolved status, reload() to "pick it up", and the
        // reload's own fresh connection would trigger the same thing again
        // — an infinite reload loop on any already-settled transaction's
        // page (not just right after cancelling one).
        return;
    }

    const code = @json($cashTransaction->code);
    const statusUrl = @json(route($routePrefix.'status', $cashTransaction));

    function showStatus(status) {
        document.getElementById('pending-block').classList.toggle('hidden', status !== 'pending');
        document.getElementById('completed-block').classList.toggle('hidden', status !== 'completed');
        document.getElementById('cancelled-block').classList.toggle('hidden', !['cancelled', 'expired', 'failed'].includes(status));
        if (status !== initialStatus) location.reload();
    }

    function poll() {
        fetch(statusUrl, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => showStatus(data.status))
            .catch(() => {});
    }

    // The broadcast already carries the new status directly, so the common
    // case never needs a fetch at all. poll() only runs as a resync when the
    // socket (re)connects, covering a status change that happened while a
    // dropped connection would otherwise have missed it — and on a fixed
    // interval, since Paybucks' own callback (not our socket) is what
    // triggers reconciliation, so a lost webhook needs this fallback.
    window.addEventListener('echo:ready', () => {
        window.Echo.channel('cash-transaction.' + code)
            .listen('.CashTransactionUpdated', (e) => showStatus(e.status));

        window.onEchoReconnect(poll);
    });

    setInterval(poll, 5000);
})();

(function () {
    const openBtn = document.getElementById('open-payment-modal');
    const modal = document.getElementById('payment-modal');
    if (!openBtn || !modal) return;

    const closeBtn = document.getElementById('close-payment-modal');
    const iframe = document.getElementById('payment-modal-iframe');

    const open = () => {
        // Lazy-loaded so the payment page only starts loading once the
        // player actually asks to see it, not the moment this page renders.
        if (iframe.src === 'about:blank') iframe.src = iframe.dataset.src;
        modal.classList.remove('hidden');
    };

    const close = () => modal.classList.add('hidden');

    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });

    @if (session('open_payment_modal'))
        open();
    @endif
})();
</script>
@endpush
@endsection
