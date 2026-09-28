@extends('layouts.app')

@section('title', $event->name)

@section('content')
@php
    $theme = $event->game?->theme() ?? \App\Support\GameTheme::for(null);
    $i18n = [
        'actionFailed' => __('Action failed.'),
        'networkError' => __('Network error.'),
        'noBetsYet' => __('No bets yet.'),
    ];
    $currency = $theme['currency'];
@endphp
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold" style="color:#f4efe4">{{ $event->name }}</h1>
        <p class="text-sm text-[#9c8f7b]">{{ $event->arena ?? __('Arena TBA') }} &middot; {{ __(ucfirst($event->status)) }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('superadmin.events.edit', $event) }}" class="gold-btn-outline">{{ __('Edit event') }}</a>
        @if ($event->status === 'upcoming')
            <form method="POST" action="{{ route('declarator.events.start', $event) }}">
                @csrf
                <button class="gold-btn">{{ __('Start event') }}</button>
            </form>
        @elseif ($event->status === 'live')
            <form data-ajax method="POST" action="{{ route('declarator.events.fights.start-next', $event) }}">
                @csrf
                <button class="gold-btn">{{ __('Start next fight') }}</button>
            </form>
            <form method="POST" action="{{ route('declarator.events.end', $event) }}" onsubmit="return confirm('{{ __('End this event?') }}')">
                @csrf
                <button class="gold-btn-outline">{{ __('End event') }}</button>
            </form>
        @endif
    </div>
</div>

@if ($videoEnabled)
    {{-- Same fallback chain as the player-facing page (this fight's own
         cockpit, then the event's preset primary, then whichever cockpit
         this event last used) — see EventController::mainStreamUrl(). Lets
         the declarator watch the same feed the players are, right from
         the panel they're running the event on, without a second tab.
         Hidden (not omitted) when there's no stream yet, same reasoning
         as the player page: a fight can go from no-cockpit to
         cockpit-assigned without a reload, and refreshPanel() below
         reveals/populates this once one becomes available. --}}
    <div id="live-stream" class="aspect-video rounded-xl overflow-hidden gold-panel bg-black mb-6" @if (! $mainStreamUrl) hidden @endif>
        <iframe id="live-stream-iframe" src="{{ $mainStreamUrl }}" class="w-full h-full" frameborder="0"
            allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
@endif

<div id="fights-panel">
    @include('declarator.events._fights_panel', ['fights' => $fights, 'fightHistory' => $fightHistory, 'event' => $event, 'theme' => $theme, 'cockpits' => $cockpits])
</div>

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const i18n = @json($i18n);
    const currency = @json($currency);
    const eventId = {{ $event->id }};
    const panelUrl = @json(route('declarator.events.fights-panel', $event));
    const panel = document.getElementById('fights-panel');

    // Tracks the src already applied so a refresh with no actual change
    // (the common case — most fight actions don't touch the stream) never
    // reloads an already-playing video.
    let currentMainStreamUrl = @json($mainStreamUrl ?? null);

    function applyMainStream(url) {
        if (url === currentMainStreamUrl) return;
        currentMainStreamUrl = url;

        const box = document.getElementById('live-stream');
        const iframe = document.getElementById('live-stream-iframe');
        if (!box || !iframe) return;

        iframe.src = url || '';
        box.hidden = ! url;
    }

    function ajaxSubmit(form) {
        const formData = new FormData(form);
        const params = new URLSearchParams();
        formData.forEach((v, k) => params.append(k, v));

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params,
        })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.success) {
                    alert(data.message || i18n.actionFailed);
                    return;
                }
                // Every action here changes a fight's status, which
                // broadcasts and refreshes the panel for every viewer
                // (including this tab) via the event.{id} subscription
                // below — but that depends on the socket connection, so
                // refresh here too as a same-tab, always-works fallback.
                refreshPanel();
            })
            .catch(() => alert(i18n.networkError));
    }

    // Delegated (not bound per-form) so it keeps working after
    // refreshPanel() replaces the whole panel's markup with fresh forms.
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form[data-ajax]');
        if (!form) return;

        e.preventDefault();
        if (form.dataset.confirm && !confirm(form.dataset.confirm)) return;
        ajaxSubmit(form);
    });

    // Cleared and re-armed on every setupFightCard() call for that fight id,
    // so a panel refresh never leaves a duplicate reconnect handler bound
    // for a detached card from before the swap.
    const reconnectUnsubs = new Map();

    function setupFightCard(card) {
        const fightId = parseInt(card.dataset.fightId, 10);
        const betsUrl = card.dataset.betsUrl;

        function refreshControls(status) {
            card.querySelectorAll('.fight-action').forEach(form => {
                const allowed = (form.dataset.visibleWhen || '').split(',');
                form.style.display = allowed.includes(status) ? '' : 'none';
            });
        }
        refreshControls(card.dataset.status);

        function fetchBets() {
            fetch(betsUrl, { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    card.querySelector('.pool-meron').textContent = currency + data.pool.meron;
                    card.querySelector('.pool-wala').textContent = currency + data.pool.wala;
                    card.querySelector('.pool-draw').textContent = currency + data.pool.draw;

                    const tbody = card.querySelector('.bettor-table');
                    tbody.innerHTML = data.bets.map(b => `
                        <tr class="border-b border-[#141a2a]">
                            <td class="py-1">${b.name}</td>
                            <td class="capitalize">${b.side}</td>
                            <td>${currency}${b.amount}</td>
                            <td class="text-[#9c8f7b]">${b.time}</td>
                        </tr>
                    `).join('') || `<tr><td colspan="4" class="py-3 text-[#9c8f7b]">${i18n.noBetsYet}</td></tr>`;
                })
                .catch(() => {});
        }
        fetchBets();

        if (window.Echo) {
            // stopListening first: setupFightCard runs again on every panel
            // refresh, and a fresh .listen() call otherwise stacks another
            // callback onto the same still-open channel subscription rather
            // than replacing it.
            const channel = window.Echo.channel('fight.' + fightId);
            channel.stopListening('.BetPoolUpdated');
            channel.listen('.BetPoolUpdated', fetchBets);

            // No polling — every bet already broadcasts on that channel — but
            // a resync still runs on reconnect in case one landed while this
            // card's connection was down.
            reconnectUnsubs.get(fightId)?.();
            reconnectUnsubs.set(fightId, window.onEchoReconnect(fetchBets));
        }
    }

    function initFightCards() {
        document.querySelectorAll('.fight-card').forEach(setupFightCard);
    }

    // Re-renders the whole panel server-side and swaps it in — simpler and
    // more robust than patching each field a fight action could change
    // (status badge, control visibility, the "awaiting declaration" note,
    // a fight moving into the Recent fights sidebar, a brand new fight
    // appearing with no card yet to patch in the first place), at the cost
    // of losing anything mid-edit inside it (there's nothing worth
    // preserving here — no free-text field, just buttons and a fight-number
    // input that only matters right when it's submitted).
    function refreshPanel() {
        fetch(panelUrl, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => {
                panel.innerHTML = data.html;
                initFightCards();
                if ('main_stream_url' in data) applyMainStream(data.main_stream_url);
            })
            .catch(() => {});
    }

    initFightCards();

    window.addEventListener('echo:ready', () => {
        // Cards rendered before Echo connected still need their fight.{id}
        // listeners attached now that window.Echo actually exists.
        initFightCards();

        // One event-wide subscription, not per-card: a brand new fight (a
        // manual "Start next fight", or the automatic one after a declare
        // or cancel) has no card here yet to listen on its own fight.{id}
        // channel, so this is the only way this screen learns about it.
        // Covers every other status change too (open/close/declare/cancel/
        // re-declare) for every fight card already on screen — one panel
        // refresh picks up all of it, for every declarator watching this
        // event, not just whoever clicked.
        window.Echo.channel('event.' + eventId).listen('.FightStatusUpdated', refreshPanel);
    });
})();
</script>
@endpush
@endsection
