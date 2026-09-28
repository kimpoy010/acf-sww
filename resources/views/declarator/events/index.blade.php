@extends('layouts.app')

@section('title', __('Events'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold" style="color:#f4efe4">{{ __('Events') }}</h1>
    @hasanyrole('declarator|superadmin')
        <a href="{{ route('superadmin.events.create') }}" class="gold-btn">+ {{ __('New event') }}</a>
    @endhasanyrole
</div>

<div class="gold-panel divide-y divide-[#141a2a]">
    @forelse ($events as $event)
        <a href="{{ route('declarator.events.show', $event) }}" class="flex items-center justify-between px-4 py-3 hover:bg-white/[0.03] transition">
            <div>
                <p class="font-semibold" style="color:#f4efe4">{{ $event->name }}</p>
                <p class="text-xs text-[#9c8f7b]">{{ $event->arena ?? __('Arena TBA') }} &middot; {{ $event->game->display_name ?? '—' }}</p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full
                {{ $event->status === 'live' ? 'bg-red-600 text-white' : ($event->status === 'upcoming' ? 'text-[#241600]' : 'text-[#9c8f7b]') }}"
                @if ($event->status !== 'live') style="background:{{ $event->status === 'upcoming' ? 'linear-gradient(180deg,#f2d68e,#c9a04a)' : '#141a2a' }}" @endif>
                {{ strtoupper($event->status) }}
            </span>
        </a>
    @empty
        <p class="px-4 py-6 text-sm text-[#9c8f7b]">{{ __('No events yet.') }}</p>
    @endforelse
</div>
@endsection
