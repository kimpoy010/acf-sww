@extends('layouts.app')

@php
    $typeLabel = $cashTransaction->type === 'deposit' ? __('Deposit') : __('Withdrawal');
    $channelLabel = $cashTransaction->channel ? \App\Services\Paybucks\PaybucksChannel::label($cashTransaction->channel) : null;
@endphp

@section('title', __(':type QR', ['type' => $typeLabel]))

@section('content')
<div class="max-w-md mx-auto text-center">
    <p class="text-[10.5px] font-extrabold tracking-[0.16em] text-[#e0793a] mb-1">{{ __('POOL SABONG') }}</p>
    <h1 class="text-xl font-extrabold mb-2">{{ __(':type request', ['type' => $typeLabel]) }}{{ $channelLabel ? ' · '.$channelLabel : '' }}</h1>
    <p class="text-3xl font-extrabold text-amber-400 mb-6" style="text-shadow: 0 0 24px rgba(251,191,36,0.25);">{{ $currencySymbol }}{{ number_format($cashTransaction->amount, 2) }}</p>

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
                    <a href="{{ $cashTransaction->payment_url }}" class="inline-block rounded-lg bg-red-600 hover:bg-red-500 transition font-semibold px-4 py-2 text-sm shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">{{ __('Open payment page') }}</a>
                </p>
                <p class="text-sm text-[#c9baaf] mb-1">{{ __('Tap the button above to pay.') }}</p>
            @else
                <div class="rounded-xl px-4 py-6 mb-4" style="background:rgba(120,53,15,0.2);border:1px solid rgba(217,119,6,0.4);">
                    <p class="font-semibold mb-1">{{ __("We couldn't generate a way to pay for this request.") }}</p>
                    <p class="text-sm text-[#c9baaf]">{{ __('Please cancel it below and try again. If this keeps happening, contact support.') }}</p>
                </div>
            @endif
        @else
            <div class="rounded-xl px-4 py-6 mb-4 bg-[#0a0e16] border border-[#141a2a]">
                <p class="text-[#c9baaf] font-semibold">{{ __('Sending your withdrawal to :channel…', ['channel' => $channelLabel ?? __('your account')]) }}</p>
                <p class="text-sm text-[#8a7a70] mt-1">{{ __('This page updates automatically once it settles.') }}</p>
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
        <a href="{{ route($routePrefix.'index') }}" class="inline-block rounded-lg bg-red-600 hover:bg-red-500 transition font-semibold px-6 py-2 shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">{{ __('Back to Cash In / Out') }}</a>
    </div>

    <div id="cancelled-block" class="{{ in_array($cashTransaction->status, ['cancelled', 'expired', 'failed']) ? '' : 'hidden' }}">
        <div class="rounded-xl px-4 py-6 mb-4 bg-[#0a0e16] border border-[#141a2a]">
            <p class="text-[#c9baaf] font-semibold text-lg">
                @if ($cashTransaction->status === 'failed')
                    {{ __('This request failed.') }} {{ $cashTransaction->provider_error_msg }}
                @else
                    {{ __('This request was :status.', ['status' => __($cashTransaction->status === 'expired' ? 'expired' : 'cancelled')]) }}
                @endif
            </p>
        </div>
        <a href="{{ route($routePrefix.'index') }}" class="inline-block rounded-lg bg-red-600 hover:bg-red-500 transition font-semibold px-6 py-2 shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">{{ __('Back to Cash In / Out') }}</a>
    </div>
</div>

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
</script>
@endpush
@endsection
