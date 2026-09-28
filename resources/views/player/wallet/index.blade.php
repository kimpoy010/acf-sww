@extends('layouts.app')

@section('title', __('My Wallet'))

@section('content')
<style>
    .gold-serif{font-family:'Cormorant Garamond',Georgia,serif}
    .gold-ornament{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 0 6px}
    .gold-ornament .line{height:1px;width:22px;background:linear-gradient(90deg,transparent,#a8791f)}
    .gold-ornament .line.r{background:linear-gradient(90deg,#a8791f,transparent)}
    .gold-ornament .diamond{width:5px;height:5px;background:#a8791f;transform:rotate(45deg)}
    .gold-eyebrow{font-size:11px;font-weight:600;letter-spacing:.28em;color:#c9a04a;margin:0 0 2px;text-align:center;text-transform:uppercase}
    .gold-h1{font-size:28px;font-weight:600;letter-spacing:.02em;margin:2px 0 18px;text-align:center;color:#f4efe4}
    .gold-card{position:relative;border-radius:14px;padding:22px 20px;background:linear-gradient(180deg,#0e0e10,#0b0b0c);border:1px solid rgba(168,121,31,.34)}
    .gold-card::before{content:"";position:absolute;top:0;left:14%;right:14%;height:1px;background:linear-gradient(90deg,transparent,#a8791f,transparent);opacity:.8}
    .gold-label{font-size:10px;font-weight:600;letter-spacing:.22em;color:#9c8f7b;text-align:center;text-transform:uppercase;margin:0 0 8px}
    .gold-balance{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:700;text-align:center;margin:0 0 18px;letter-spacing:.01em;
        background:linear-gradient(180deg,#c9a04a,#a8791f 60%,#5e4517);-webkit-background-clip:text;background-clip:text;color:transparent}

    .gold-btn-frame{position:relative;display:block;overflow:hidden;padding:2px;border-radius:9px;background:linear-gradient(155deg,#fdf0c8 0%,#e8c15f 22%,#a8791f 48%,#f0cf7e 68%,#7a591c 100%);box-shadow:0 6px 14px -8px rgba(0,0,0,.6);text-decoration:none}
    .gold-btn-corner{position:absolute;width:4px;height:4px;background:#fff3d6;box-shadow:0 0 2px 0 rgba(255,243,214,.8)}
    .gold-btn-corner.tl{top:1px;left:1px} .gold-btn-corner.tr{top:1px;right:1px}
    .gold-btn-corner.bl{bottom:1px;left:1px} .gold-btn-corner.br{bottom:1px;right:1px}
    .gold-btn-fill{position:relative;display:block;overflow:hidden;border-radius:7px;padding:12px 0;text-align:center;font-weight:800;font-size:12px;letter-spacing:.12em;text-transform:uppercase}
    .gold-btn-fill::before{content:"";position:absolute;inset:0;background:linear-gradient(115deg,transparent 38%,rgba(255,255,255,.32) 50%,rgba(255,255,255,.03) 60%,transparent 70%);pointer-events:none}
    .gold-btn-primary .gold-btn-fill{background:linear-gradient(180deg,#f2d68e,#c9a04a 45%,#8a611a 100%);color:#241600;box-shadow:inset 0 1px 0 rgba(255,255,255,.55),inset 0 -6px 10px -6px rgba(50,32,0,.55);text-shadow:0 1px 0 rgba(255,255,255,.3)}
    .gold-btn-secondary .gold-btn-fill{background:linear-gradient(180deg,#332d24,#161310 55%,#0a0806);color:#c9a04a;box-shadow:inset 0 1px 0 rgba(255,255,255,.08),inset 0 -5px 8px -6px rgba(0,0,0,.75)}

    .gold-section-title{font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#9c8f7b;margin:0 0 10px;padding:0 2px}
    .gold-tabs{display:flex;gap:18px;padding:0 4px 10px;margin-bottom:2px;border-bottom:1px solid rgba(255,255,255,.06);overflow-x:auto}
    .gold-tab{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6d6252;padding-bottom:9px;position:relative;white-space:nowrap;background:none;border:none;cursor:pointer}
    .gold-tab.is-active{color:#c9a04a;font-weight:700}
    .gold-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;border-radius:1px;background:linear-gradient(90deg,#c9a04a,#a8791f);box-shadow:0 0 6px 0 rgba(168,121,31,.6)}

    .gold-filter-input{background:#0b0b0c;border:1px solid rgba(168,121,31,.2);color:#f4efe4}
    .gold-filter-input:focus{outline:none;border-color:rgba(168,121,31,.5);box-shadow:0 0 0 2px rgba(168,121,31,.25)}
    .gold-filter-btn{background:linear-gradient(180deg,#c9a04a,#a8791f);color:#241600;border:none;border-radius:8px;font-weight:800;font-size:11px;letter-spacing:.06em;padding:7px 14px;text-transform:uppercase}
</style>

<div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
<p class="gold-eyebrow">{{ __('Pool Sabong') }}</p>
<h1 class="gold-serif gold-h1">{{ __('My Wallet') }}</h1>

<div class="gold-card mb-4">
    <p class="gold-label">{{ __('Wallet Balance') }}</p>
    <p class="gold-balance">{{ $currencySymbol }}<span data-wallet-balance="main">{{ number_format($wallet->main_balance ?? 0, 2) }}</span></p>
    @if (($wallet->pending_withdrawal ?? 0) >= 0.01)
        <p class="text-xs text-center -mt-3 mb-4" style="color:#c9a04a">{{ __(':amount held for a pending withdrawal', ['amount' => $currencySymbol.number_format($wallet->pending_withdrawal, 2)]) }}</p>
    @endif

    <div class="grid grid-cols-2 gap-2.5">
        <a href="{{ route('play.cash.index') }}#cash-in" class="gold-btn-frame gold-btn-primary">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Deposit') }}</span>
        </a>
        <a href="{{ route('play.cash.index') }}#cash-out" class="gold-btn-frame gold-btn-secondary">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Withdraw') }}</span>
        </a>
    </div>
</div>

<div class="rounded-xl border border-[#141a2a] bg-[#0a0e16] p-4">
    <p class="gold-section-title">{{ __('Recent Activity') }}</p>

    <div class="gold-tabs" id="wallet-tabs">
        <button type="button" class="wallet-tab gold-tab" data-tab="all">{{ __('All') }}</button>
        <button type="button" class="wallet-tab gold-tab" data-tab="bets">{{ __('Bets') }}</button>
        <button type="button" class="wallet-tab gold-tab" data-tab="deposits">{{ __('Deposits') }}</button>
        <button type="button" class="wallet-tab gold-tab" data-tab="withdrawals">{{ __('Withdrawals') }}</button>
    </div>

    <div class="flex flex-wrap items-end gap-2 mt-3 mb-2">
        <div id="wallet-filter-type" hidden>
            <label class="block text-[10px] text-[#8a7a70] mb-1">{{ __('Type') }}</label>
            <select id="wallet-tx-filter-type" class="rounded-lg bg-[#05070b] border border-[#141a2a] px-2 py-1.5 text-xs">
                <option value="">{{ __('All types') }}</option>
                <option value="bet">{{ __('Bet placed') }}</option>
                <option value="payout">{{ __('Bet payout') }}</option>
                <option value="refund">{{ __('Bet refund') }}</option>
                <option value="deposit">{{ __('Cash deposit') }}</option>
                <option value="withdrawal">{{ __('Cash withdrawal') }}</option>
                <option value="reversal">{{ __('Payout reversal') }}</option>
            </select>
        </div>
        <div id="wallet-filter-event" hidden>
            <label class="block text-[10px] text-[#8a7a70] mb-1">{{ __('Event') }}</label>
            <select id="wallet-tx-filter-event" class="rounded-lg bg-[#05070b] border border-[#141a2a] px-2 py-1.5 text-xs">
                <option value="">{{ __('All Bets') }}</option>
                @foreach ($playerEvents as $playerEvent)
                    <option value="{{ $playerEvent->id }}">{{ $playerEvent->name }}</option>
                @endforeach
            </select>
        </div>
        <div id="wallet-filter-date-range" hidden>
            <label class="block text-[10px] text-[#8a7a70] mb-1">{{ __('Date range') }}</label>
            <div class="relative">
                <input type="text" id="wallet-tx-filter-date-range" data-date-range
                       data-range-from="#wallet-tx-filter-date-from" data-range-to="#wallet-tx-filter-date-to"
                       placeholder="{{ __('Select dates') }}" autocomplete="off" readonly
                       class="rounded-lg bg-[#05070b] border border-[#141a2a] pl-2 pr-6 py-1.5 text-xs cursor-pointer w-52">
                <button type="button" data-date-range-clear hidden
                        class="absolute right-1 top-1/2 -translate-y-1/2 text-[#8a7a70] hover:text-white text-sm leading-none">&times;</button>
            </div>
            <input type="hidden" id="wallet-tx-filter-date-from">
            <input type="hidden" id="wallet-tx-filter-date-to">
        </div>
        <div id="wallet-filter-amount" hidden>
            <label class="block text-[10px] text-[#8a7a70] mb-1">{{ __('Amount') }}</label>
            <input type="text" inputmode="decimal" id="wallet-tx-filter-amount" placeholder="{{ __('Amount') }}"
                   class="amount-input w-24 rounded-lg bg-[#05070b] border border-[#141a2a] px-2 py-1.5 text-xs">
        </div>
        <button type="button" id="wallet-tx-filter-apply" class="gold-filter-btn">{{ __('Filter') }}</button>
        <button type="button" id="wallet-tx-filter-clear" class="text-xs text-[#8a7a70] hover:text-[#c9a04a] transition px-2 py-1.5" hidden>{{ __('Clear') }}</button>
    </div>

    <div id="wallet-tx-results">
        @include('player.partials.wallet-transaction-rows')
    </div>
</div>

<!-- Transaction detail modal -->
<div id="wallet-tx-modal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="w-full sm:max-w-sm bg-[#0a0e16] border border-[#141a2a] rounded-t-2xl sm:rounded-2xl p-5 pb-6">
        <div class="flex items-center justify-between mb-4">
            <p class="font-semibold text-[#f5efe9]">{{ __('Transaction details') }}</p>
            <button type="button" id="wallet-tx-modal-close" class="text-[#8a7a70] hover:text-white transition text-xl leading-none">&times;</button>
        </div>

        <p class="text-center text-2xl font-extrabold mb-1" id="wallet-tx-modal-amount"></p>
        <p class="text-center text-sm text-[#8a7a70] mb-5" id="wallet-tx-modal-title"></p>

        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Date & time') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="wallet-tx-modal-datetime"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Type') }}</dt>
                <dd class="text-[#f5efe9] text-right capitalize" id="wallet-tx-modal-type"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Category') }}</dt>
                <dd class="text-[#f5efe9] text-right capitalize" id="wallet-tx-modal-reference"></dd>
            </div>
            <div class="flex justify-between" id="wallet-tx-modal-event-row" hidden>
                <dt class="text-[#8a7a70]">{{ __('Event') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="wallet-tx-modal-event"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-[#8a7a70]">{{ __('Balance after') }}</dt>
                <dd class="text-[#f5efe9] text-right" id="wallet-tx-modal-balance"></dd>
            </div>
        </dl>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('wallet-tx-modal');
        const results = document.getElementById('wallet-tx-results');
        if (!modal || !results) return;

        const amountEl = document.getElementById('wallet-tx-modal-amount');
        const titleEl = document.getElementById('wallet-tx-modal-title');
        const datetimeEl = document.getElementById('wallet-tx-modal-datetime');
        const typeEl = document.getElementById('wallet-tx-modal-type');
        const referenceEl = document.getElementById('wallet-tx-modal-reference');
        const eventRow = document.getElementById('wallet-tx-modal-event-row');
        const eventEl = document.getElementById('wallet-tx-modal-event');
        const balanceEl = document.getElementById('wallet-tx-modal-balance');
        const closeBtn = document.getElementById('wallet-tx-modal-close');

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
        // swapped in by the tab/filter/pagination fetch below — which
        // replace #wallet-tx-results' innerHTML wholesale — open the modal
        // too, without needing to re-bind listeners after every fetch.
        results.addEventListener('click', (e) => {
            const row = e.target.closest('.wallet-tx-row');
            if (row) openModal(row);
        });

        // --- Tabs + per-tab filters ---------------------------------
        const baseUrl = @json(route('play.wallet.index'));
        const tabs = document.querySelectorAll('.wallet-tab');
        const typeGroup = document.getElementById('wallet-filter-type');
        const eventGroup = document.getElementById('wallet-filter-event');
        const dateRangeGroup = document.getElementById('wallet-filter-date-range');
        const amountGroup = document.getElementById('wallet-filter-amount');
        const typeSelect = document.getElementById('wallet-tx-filter-type');
        const eventSelect = document.getElementById('wallet-tx-filter-event');
        const dateRangeInput = document.getElementById('wallet-tx-filter-date-range');
        const dateFromInput = document.getElementById('wallet-tx-filter-date-from');
        const dateToInput = document.getElementById('wallet-tx-filter-date-to');
        const amountInput = document.getElementById('wallet-tx-filter-amount');
        const applyBtn = document.getElementById('wallet-tx-filter-apply');
        const clearBtn = document.getElementById('wallet-tx-filter-clear');

        // Which filter groups apply to each tab — drives both which
        // controls show and which query params load() sends.
        const TAB_FILTERS = {
            all: ['type', 'date_from', 'date_to'],
            bets: ['event'],
            deposits: ['date_from', 'date_to', 'amount'],
            withdrawals: ['date_from', 'date_to', 'amount'],
        };

        // Hydrated from what the server actually rendered this page with —
        // otherwise a bookmarked/shared filtered URL, or the back button
        // landing here, would show tab/filter controls that don't match
        // the rows already on screen.
        const state = {
            tab: @json($tab),
            type: @json($type ?? ''),
            event_id: @json($eventId ? (string) $eventId : ''),
            amount: @json($amount !== null ? (string) $amount : ''),
            date_from: @json($dateFrom ?? ''),
            date_to: @json($dateTo ?? ''),
        };
        typeSelect.value = state.type;
        eventSelect.value = state.event_id;
        amountInput.value = state.amount;
        dateFromInput.value = state.date_from;
        dateToInput.value = state.date_to;

        function resetFilterInputs() {
            typeSelect.value = '';
            eventSelect.value = '';
            if (dateRangeInput._flatpickr) {
                dateRangeInput._flatpickr.clear();
            } else {
                dateFromInput.value = '';
                dateToInput.value = '';
            }
            amountInput.value = '';
            state.type = state.event_id = state.amount = state.date_from = state.date_to = '';
        }

        function updateFilterVisibility() {
            const active = TAB_FILTERS[state.tab];
            typeGroup.hidden = !active.includes('type');
            eventGroup.hidden = !active.includes('event');
            dateRangeGroup.hidden = !active.includes('date_from');
            amountGroup.hidden = !active.includes('amount');
        }

        function setActiveTabStyle() {
            tabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.tab === state.tab);
            });
        }

        function hasActiveFilters() {
            return Object.keys(state).some((key) => key !== 'tab' && state[key]);
        }

        function load(page) {
            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('tab', state.tab);
            if (state.type) url.searchParams.set('type', state.type);
            if (state.event_id) url.searchParams.set('event_id', state.event_id);
            if (state.amount) url.searchParams.set('amount', state.amount);
            if (state.date_from) url.searchParams.set('date_from', state.date_from);
            if (state.date_to) url.searchParams.set('date_to', state.date_to);
            if (page && page > 1) url.searchParams.set('page', page);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
                .then((r) => r.text())
                .then((html) => { results.innerHTML = html; })
                .catch(() => {});

            clearBtn.hidden = !hasActiveFilters();
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                if (tab.dataset.tab === state.tab) return;
                state.tab = tab.dataset.tab;
                resetFilterInputs();
                updateFilterVisibility();
                setActiveTabStyle();
                load(1);
            });
        });

        applyBtn.addEventListener('click', () => {
            state.type = typeSelect.value;
            state.event_id = eventSelect.value;
            // amount-input fields display comma-formatted text while
            // focused/filled (see amount-format.js) — this reads outside
            // any form submit event, so it must go through parseAmount()
            // itself instead of relying on that script's submit listener.
            state.amount = window.parseAmount ? window.parseAmount(amountInput.value) : amountInput.value;
            state.date_from = dateFromInput.value;
            state.date_to = dateToInput.value;
            load(1);
        });

        clearBtn.addEventListener('click', () => {
            resetFilterInputs();
            load(1);
        });

        // Pagination links render as plain <a href> inside the results
        // container (Laravel's default pagination view) — intercept clicks
        // on them so paging stays in place too, rather than only the tabs/
        // filters above.
        results.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link) return;

            e.preventDefault();
            const page = new URL(link.href, window.location.origin).searchParams.get('page') || 1;
            load(page);
        });

        updateFilterVisibility();
        setActiveTabStyle();
        clearBtn.hidden = !hasActiveFilters();
    })();
</script>
@endpush
@endsection
