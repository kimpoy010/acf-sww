@extends('layouts.app')

@section('title', __('Superadmin'))

@section('content')
<h1 class="text-2xl font-bold mb-6" style="color:#f4efe4">{{ __('Superadmin Dashboard') }}</h1>

<div class="grid md:grid-cols-3 gap-4 mb-8">
    @can('manage-events')
        <a href="{{ route('superadmin.events.create') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">＋ {{ __('New event') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Schedule a new pool-sabong card.') }}</p>
        </a>
    @endcan
    <a href="{{ route('declarator.events.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
        <p class="font-semibold">🐓 {{ __('Run fights') }}</p>
        <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Open the declarator console.') }}</p>
    </a>
    @can('manage-wallets')
        <a href="{{ route('superadmin.wallets.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">💰 {{ __('Player wallets') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __("Credit or debit a player's balance.") }}</p>
        </a>
    @endcan
    @can('manage-agents')
        <a href="{{ route('superadmin.agents.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">🧑‍💼 {{ __('Agents') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Manage agent levels and commission rates.') }}</p>
        </a>
    @endcan
    @can('manage-staff')
        <a href="{{ route('superadmin.staff.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">🧑‍✈️ {{ __('Staff') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Create teller and declarator accounts.') }}</p>
        </a>
    @endcan
    @can('view-audit-log')
        <a href="{{ route('superadmin.audit.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">🕵️ {{ __('Audit Trail') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Every action, by everyone, tamper-evident.') }}</p>
        </a>
    @endcan
    @can('manage-rfid-terminals')
        <a href="{{ route('superadmin.rfid-terminals.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">📡 {{ __('RFID Terminals') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Manage self-service betting/top-up kiosks.') }}</p>
        </a>
    @endcan
    @can('manage-cockpits')
        <a href="{{ route('superadmin.cockpits.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">📹 {{ __('Cockpits') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __("Set each ring's video feed URL.") }}</p>
        </a>
    @endcan
    @can('manage-cockpit-presets')
        <a href="{{ route('superadmin.cockpit-presets.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">🎛️ {{ __('Cockpit Presets') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Group cockpits to assign when creating an event.') }}</p>
        </a>
    @endcan
    @can('view-reports')
        <a href="{{ route('superadmin.reports.income') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">📊 {{ __('Income report') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('House income per fight — staked vs. paid out.') }}</p>
        </a>
        <a href="{{ route('superadmin.reports.accounting.events') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">📒 {{ __('Betting accounting') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Every event, fight, and individual bet — the full ledger.') }}</p>
        </a>
        {{-- Teller accounts are retired (see DisableTellerRoutes) — this
             card is deliberately gone since no new data can ever land here,
             but the report itself (superadmin.reports.teller-cash-flow) is
             still reachable by URL for whatever historical data predates
             the retirement. --}}
    @endcan
    @can('manage-roles')
        <a href="{{ route('superadmin.roles.index') }}" class="gold-panel p-4 hover:brightness-110 transition">
            <p class="font-semibold">🛡️ {{ __('Roles & Permissions') }}</p>
            <p class="text-sm text-[#9c8f7b] mt-1">{{ __('Control which admin sections each role can reach.') }}</p>
        </a>
    @endcan
</div>

@can('manage-games')
    @if ($game)
        <div class="gold-panel p-4 mb-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">{{ __('Pool-Sabong settings') }}</h2>
                <a href="{{ route('superadmin.games.edit', $game) }}" class="text-sm hover:underline" style="color:#c9a04a">{{ __('Edit') }}</a>
            </div>
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><dt class="text-[#9c8f7b]">{{ __('Plasada') }}</dt><dd class="font-semibold">{{ $game->plasada }}%</dd></div>
                <div><dt class="text-[#9c8f7b]">{{ __('Mode') }}</dt><dd class="font-semibold">{{ $game->plasada_mode }}</dd></div>
                <div><dt class="text-[#9c8f7b]">{{ __('Draw multiplier') }}</dt><dd class="font-semibold">{{ $game->draw_multiplier }}x</dd></div>
                <div><dt class="text-[#9c8f7b]">{{ __('Max draw bet') }}</dt><dd class="font-semibold">{{ $game->theme()['currency'] }}{{ number_format($game->max_draw_bet, 0) }}</dd></div>
            </dl>
        </div>
    @endif
@endcan

<div class="gold-panel divide-y divide-[#141a2a]">
    <div class="px-4 py-2 text-sm text-[#9c8f7b]">{{ __('Recent events') }}</div>
    @forelse ($events as $event)
        <div class="flex items-center justify-between px-4 py-3 hover:bg-white/[0.03] transition">
            <a href="{{ route('declarator.events.show', $event) }}" class="flex-1">{{ $event->name }}</a>
            <div class="flex items-center gap-3">
                <span class="text-xs px-2 py-0.5 rounded-full {{ $event->status === 'live' ? 'bg-red-600 text-white' : '' }}" @if ($event->status !== 'live') style="background:#141a2a" @endif>{{ strtoupper($event->status) }}</span>
                @can('manage-events')
                    <a href="{{ route('superadmin.events.edit', $event) }}" class="text-sm hover:underline" style="color:#c9a04a">{{ __('Edit') }}</a>
                @endcan
            </div>
        </div>
    @empty
        <p class="px-4 py-6 text-sm text-[#9c8f7b]">{{ __('No events yet.') }}</p>
    @endforelse
</div>
@endsection
