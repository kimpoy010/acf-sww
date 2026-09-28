@extends('layouts.app')

@section('title', __('Transactions'))

@section('content')
@include('player.partials.gold-theme-styles')

<div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
<p class="gold-eyebrow">{{ __('Pool Sabong') }}</p>
<h1 class="gold-serif gold-h1">{{ __('Transactions') }}</h1>

<div class="rounded-xl border border-[#141a2a] bg-[#0a0e16] p-4">
    <p class="gold-section-title">{{ __('All Transactions') }}</p>

    <div class="flex flex-wrap items-end gap-2 mb-2">
        <div>
            <label class="block text-[10px] text-[#8a7a70] mb-1">{{ __('Type') }}</label>
            <select id="tx-filter-type" class="rounded-lg bg-[#05070b] border border-[#141a2a] px-2 py-1.5 text-xs">
                <option value="">{{ __('All types') }}</option>
                <option value="bet">{{ __('Bet placed') }}</option>
                <option value="payout">{{ __('Bet payout') }}</option>
                <option value="refund">{{ __('Bet refund') }}</option>
                <option value="deposit">{{ __('Cash deposit') }}</option>
                <option value="withdrawal">{{ __('Cash withdrawal') }}</option>
                <option value="reversal">{{ __('Payout reversal') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] text-[#8a7a70] mb-1">{{ __('Date range') }}</label>
            <div class="relative">
                <input type="text" id="tx-filter-date-range" data-date-range
                       data-range-from="#tx-filter-date-from" data-range-to="#tx-filter-date-to"
                       placeholder="{{ __('Select dates') }}" autocomplete="off" readonly
                       class="rounded-lg bg-[#05070b] border border-[#141a2a] pl-2 pr-6 py-1.5 text-xs cursor-pointer w-52">
                <button type="button" data-date-range-clear hidden
                        class="absolute right-1 top-1/2 -translate-y-1/2 text-[#8a7a70] hover:text-white text-sm leading-none">&times;</button>
            </div>
            <input type="hidden" id="tx-filter-date-from">
            <input type="hidden" id="tx-filter-date-to">
        </div>
        <button type="button" id="tx-filter-apply" class="gold-filter-btn">{{ __('Filter') }}</button>
        <button type="button" id="tx-filter-clear" class="text-xs text-[#8a7a70] hover:text-[#c9a04a] transition px-2 py-1.5" hidden>{{ __('Clear') }}</button>
    </div>

    <div id="tx-results">
        @include('player.partials.wallet-transaction-rows')
    </div>
</div>

<!-- Transaction detail modal -->
<div id="tx-modal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="w-full sm:max-w-sm bg-[#0a0e16] border border-[#141a2a] rounded-t-2xl sm:rounded-2xl p-5 pb-6">
        <div class="flex items-center justify-between mb-4">
            <p class="font-semibold text-[#f5efe9]">{{ __('Transaction details') }}</p>
            <button type="button" id="tx-modal-close" class="text-[#8a7a70] hover:text-white transition text-xl leading-none">&times;</button>
        </div>

        <p class="text-center text-2xl font-extrabold mb-1" id="tx-modal-amount"></p>
        <p class="text-center text-sm text-[#8a7a70] mb-5" id="tx-modal-title"></p>

        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Date & time') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="tx-modal-datetime"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Type') }}</dt>
                <dd class="text-[#f5efe9] text-right capitalize" id="tx-modal-type"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Category') }}</dt>
                <dd class="text-[#f5efe9] text-right capitalize" id="tx-modal-reference"></dd>
            </div>
            <div class="flex justify-between" id="tx-modal-event-row" hidden>
                <dt class="text-[#8a7a70]">{{ __('Event') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="tx-modal-event"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Balance after') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="tx-modal-balance"></dd>
            </div>
        </dl>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('tx-modal');
        const results = document.getElementById('tx-results');
        if (!modal || !results) return;

        const amountEl = document.getElementById('tx-modal-amount');
        const titleEl = document.getElementById('tx-modal-title');
        const datetimeEl = document.getElementById('tx-modal-datetime');
        const typeEl = document.getElementById('tx-modal-type');
        const referenceEl = document.getElementById('tx-modal-reference');
        const eventRow = document.getElementById('tx-modal-event-row');
        const eventEl = document.getElementById('tx-modal-event');
        const balanceEl = document.getElementById('tx-modal-balance');
        const closeBtn = document.getElementById('tx-modal-close');

        const openModal = (row) => {
            const isCredit = row.dataset.type === 'credit';
            amountEl.textContent = (isCredit ? '+' : '-') + @json($currencySymbol) + row.dataset.amount;
            amountEl.className = 'text-center text-2xl font-extrabold mb-1 ' + (isCredit ? 'text-emerald-400' : 'text-red-400');
            titleEl.textContent = row.dataset.title;
            datetimeEl.textContent = row.dataset.datetime;
            typeEl.textContent = row.dataset.type;
            referenceEl.textContent = row.dataset.referenceType.replace(/_/g, ' ');
            eventRow.hidden = !row.dataset.eventName;
            eventEl.textContent = row.dataset.eventName;
            balanceEl.textContent = @json($currencySymbol) + row.dataset.balance;
            modal.classList.remove('hidden');
        };

        const closeModal = () => modal.classList.add('hidden');

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // Delegated on the results container (not bound per-row) so rows
        // swapped in by the filter/pagination fetch below — which replace
        // #tx-results' innerHTML wholesale — open the modal too, without
        // needing to re-bind listeners after every fetch.
        results.addEventListener('click', (e) => {
            const row = e.target.closest('.wallet-tx-row');
            if (row) openModal(row);
        });

        // --- Filters --------------------------------------------------
        const baseUrl = @json(route('play.transactions.index'));
        const typeSelect = document.getElementById('tx-filter-type');
        const dateRangeInput = document.getElementById('tx-filter-date-range');
        const dateFromInput = document.getElementById('tx-filter-date-from');
        const dateToInput = document.getElementById('tx-filter-date-to');
        const applyBtn = document.getElementById('tx-filter-apply');
        const clearBtn = document.getElementById('tx-filter-clear');

        // Hydrated from what the server actually rendered this page with —
        // otherwise a bookmarked/shared filtered URL, or the back button
        // landing here, would show filter controls that don't match the
        // rows already on screen.
        const state = {
            type: @json($type ?? ''),
            date_from: @json($dateFrom ?? ''),
            date_to: @json($dateTo ?? ''),
        };
        typeSelect.value = state.type;
        dateFromInput.value = state.date_from;
        dateToInput.value = state.date_to;

        function hasActiveFilters() {
            return Object.values(state).some(Boolean);
        }

        function load(page) {
            const url = new URL(baseUrl, window.location.origin);
            if (state.type) url.searchParams.set('type', state.type);
            if (state.date_from) url.searchParams.set('date_from', state.date_from);
            if (state.date_to) url.searchParams.set('date_to', state.date_to);
            if (page && page > 1) url.searchParams.set('page', page);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
                .then((r) => r.text())
                .then((html) => { results.innerHTML = html; })
                .catch(() => {});

            clearBtn.hidden = !hasActiveFilters();
        }

        applyBtn.addEventListener('click', () => {
            state.type = typeSelect.value;
            state.date_from = dateFromInput.value;
            state.date_to = dateToInput.value;
            load(1);
        });

        clearBtn.addEventListener('click', () => {
            typeSelect.value = '';
            if (dateRangeInput._flatpickr) {
                dateRangeInput._flatpickr.clear();
            } else {
                dateFromInput.value = '';
                dateToInput.value = '';
            }
            state.type = state.date_from = state.date_to = '';
            load(1);
        });

        // Pagination links render as plain <a href> inside the results
        // container (Laravel's default pagination view) — intercept clicks
        // on them so paging stays in place too, rather than only the
        // filters above.
        results.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link) return;

            e.preventDefault();
            const page = new URL(link.href, window.location.origin).searchParams.get('page') || 1;
            load(page);
        });

        clearBtn.hidden = !hasActiveFilters();
    })();
</script>
@endpush
@endsection
