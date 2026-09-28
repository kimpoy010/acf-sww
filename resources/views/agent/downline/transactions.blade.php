@extends('layouts.app')

@section('title', __('Downline Transactions'))

@section('content')
<a href="{{ route('agent.dashboard') }}" class="text-sm text-[#9c8f7b] hover:text-[#c9a04a] transition">&larr; {{ __('Back to Dashboard') }}</a>

<div class="flex items-center justify-between mb-6 mt-2 gap-3 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold" style="color:#f4efe4">{{ $targetUser->displayName() }}</h1>
        <p class="text-sm text-[#9c8f7b]">{{ $isPlayer ? __('Downline player') : __('Downline agent') }}</p>
    </div>
    <div class="text-right">
        <p class="text-xs text-[#9c8f7b] uppercase tracking-wide">{{ __('Balance') }}</p>
        <p class="text-xl font-extrabold text-emerald-400">{{ $currencySymbol }}{{ number_format($wallet->main_balance ?? 0, 2) }}</p>
    </div>
</div>

@php
    $tabLabels = $isPlayer
        ? ['all' => __('All'), 'bets' => __('Bets'), 'deposits' => __('Deposits'), 'withdrawals' => __('Withdrawals')]
        : ['all' => __('All'), 'deposits' => __('Deposits'), 'withdrawals' => __('Withdrawals')];
@endphp
<div class="flex gap-1.5 gold-panel p-1 mb-4 max-w-xl" id="downline-tx-tabs">
    @foreach ($tabLabels as $tabKey => $tabLabel)
        <button type="button" data-tab="{{ $tabKey }}"
           class="downline-tx-tab flex-1 text-center rounded-lg py-2 text-xs font-bold transition {{ $tab === $tabKey ? 'is-active' : 'text-[#9c8f7b] hover:text-[#f4efe4]' }}">
            {{ $tabLabel }}
        </button>
    @endforeach
</div>

<form id="downline-tx-filter-form" method="GET" action="{{ route('agent.downline.transactions', $targetUser) }}" class="gold-panel p-4 mb-6 flex flex-wrap items-end gap-3">
    <input type="hidden" name="tab" id="downline-tx-tab-field" value="{{ $tab }}">
    @include('partials.date-range-field', ['id' => 'downline-tx-date', 'fromName' => 'date_from', 'toName' => 'date_to', 'fromValue' => $dateFrom, 'toValue' => $dateTo])
    @if ($isPlayer)
        <div id="downline-tx-event-group" @if ($tab !== 'bets') hidden @endif>
            <label class="block text-xs text-[#9c8f7b] mb-1">{{ __('Event') }}</label>
            <select name="event_id" id="downline-tx-event-select" class="gold-input px-3 py-2 text-sm">
                <option value="">{{ __('All events') }}</option>
                @foreach ($playerEvents as $playerEvent)
                    <option value="{{ $playerEvent->id }}" @selected($eventId === $playerEvent->id)>{{ $playerEvent->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <button class="gold-btn gold-btn-sm">{{ __('Filter') }}</button>
    <a id="downline-tx-clear" href="{{ route('agent.downline.transactions', [$targetUser, 'tab' => $tab]) }}" @if (! ($dateFrom || $dateTo || $eventId)) hidden @endif class="text-sm text-[#9c8f7b] hover:text-[#c9a04a] transition px-2 py-2">{{ __('Clear') }}</a>
</form>

<div id="downline-tx-results">
    @include('agent.downline._transactions-rows')
</div>

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('downline-tx-filter-form');
        const results = document.getElementById('downline-tx-results');
        const tabField = document.getElementById('downline-tx-tab-field');
        const eventGroup = document.getElementById('downline-tx-event-group');
        const clearLink = document.getElementById('downline-tx-clear');
        const tabs = document.querySelectorAll('.downline-tx-tab');
        const baseUrl = @json(route('agent.downline.transactions', $targetUser));

        function updateEventVisibility() {
            if (eventGroup) eventGroup.hidden = tabField.value !== 'bets';
        }

        function setActiveTabStyle() {
            tabs.forEach((btn) => {
                const active = btn.dataset.tab === tabField.value;
                btn.classList.toggle('is-active', active);
                btn.classList.toggle('text-[#9c8f7b]', !active);
            });
        }

        function hasActiveFilters() {
            return Array.from(form.elements).some((el) => {
                if (el === tabField || el.type === 'hidden' || el.type === 'submit' || el.type === 'button') return false;
                return el.value !== '';
            });
        }

        function buildUrl() {
            const params = new URLSearchParams(new FormData(form));
            return `${baseUrl}?${params.toString()}`;
        }

        function go(url) {
            window.AjaxList.load(url, results);
            clearLink.hidden = !hasActiveFilters();
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            go(buildUrl());
        });

        // Switching tabs resets every filter, same convention as the
        // player and superadmin wallet transaction pages.
        tabs.forEach((btn) => {
            btn.addEventListener('click', () => {
                if (btn.dataset.tab === tabField.value) return;
                tabField.value = btn.dataset.tab;
                window.AjaxList.clearFormFields(form);
                updateEventVisibility();
                setActiveTabStyle();
                go(buildUrl());
            });
        });

        clearLink.addEventListener('click', (e) => {
            e.preventDefault();
            window.AjaxList.clearFormFields(form);
            go(buildUrl());
        });

        window.AjaxList.bindPagination(results, go);

        updateEventVisibility();
    })();
</script>
@endpush
@endsection
