@extends('layouts.app')

@section('title', __('Live Events'))

@section('content')
<style>
    /* Static gold glow on the live/ongoing tiles — this is the event
       listing's own decorative border, distinct from the Meron/Wala side
       colors used on the actual bet buttons (pool-betting.blade.php),
       which stay as they are. */
    .live-tile-neon {
        border-width: 2px;
        border-style: solid;
        border-color: rgba(168, 121, 31, .6);
        box-shadow: 0 0 14px 1px rgba(168, 121, 31, .35), 0 0 30px 4px rgba(168, 121, 31, .15);
    }
    .event-tile-name {
        color: #c9a04a;
    }
</style>
<div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
<p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
<h1 class="gold-serif gold-h1">{{ __('Live Events') }}</h1>
<p class="text-sm text-[#8a7a70] -mt-4 mb-6 text-center">{{ __('Pick a live event to start betting') }}</p>

<div class="mb-10">
    <h2 class="flex items-center gap-2 text-[11.5px] font-extrabold uppercase tracking-[0.1em] text-red-400 mb-3">
        <span class="relative flex h-[7px] w-[7px]">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
            <span class="relative inline-flex h-[7px] w-[7px] rounded-full bg-red-500 shadow-[0_0_8px_2px_rgba(239,68,68,0.55)]"></span>
        </span>
        {{ __('Live now') }}
    </h2>
    @if ($liveEvents->isEmpty())
        <p class="text-[#8a7a70] text-sm">{{ __('No live events right now. Check back soon.') }}</p>
    @else
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            @foreach ($liveEvents as $event)
                <div class="group relative block overflow-hidden rounded-xl bg-[#0b0b0c] transition live-tile-neon">
                    <div class="relative aspect-video bg-black">
                        @if ($event->displayBannerUrl())
                            <img src="{{ $event->displayBannerUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="absolute inset-0" style="background:radial-gradient(circle at 50% 40%, rgba(168,121,31,.22), #05070b 75%);"></div>
                        @endif
                        <span class="absolute top-2 left-2 flex items-center gap-1 rounded-full bg-red-600 px-2 py-0.5 text-[10px] sm:text-[11px] font-bold uppercase text-white shadow-[0_0_10px_1px_rgba(239,68,68,0.6)]">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                            {{ __('LIVE') }}
                        </span>
                    </div>
                    <div class="p-2.5 sm:p-4">
                        <p class="event-tile-name font-semibold text-sm sm:text-base truncate">{{ $event->name }}</p>
                        <p class="text-xs sm:text-sm text-[#8a7a70] truncate">{{ $event->arena ?? __('Arena TBA') }}</p>
                    </div>
                    <a href="{{ route('play.events.enter', $event) }}" class="absolute inset-0" aria-label="{{ $event->name }}"></a>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if ($ongoingFights->isNotEmpty())
    <div class="mb-10">
        <h2 class="flex items-center gap-2 text-[11.5px] font-extrabold uppercase tracking-[0.1em] text-red-400 mb-3">
            <span class="relative flex h-[7px] w-[7px]">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                <span class="relative inline-flex h-[7px] w-[7px] rounded-full bg-red-500 shadow-[0_0_8px_2px_rgba(239,68,68,0.55)]"></span>
            </span>
            {{ __('Ongoing fights') }}
        </h2>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            @foreach ($ongoingFights as $item)
                <a href="{{ route('play.pool-fight', $item['fight']) }}" class="group relative block overflow-hidden rounded-xl bg-[#0b0b0c] transition live-tile-neon">
                    <div class="relative aspect-video bg-black">
                        @if ($item['event']->displayBannerUrl())
                            <img src="{{ $item['event']->displayBannerUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="absolute inset-0" style="background:radial-gradient(circle at 50% 40%, rgba(168,121,31,.22), #05070b 75%);"></div>
                        @endif
                        <span class="absolute top-2 left-2 flex items-center gap-1 rounded-full bg-red-600 px-2 py-0.5 text-[10px] sm:text-[11px] font-bold uppercase text-white shadow-[0_0_10px_1px_rgba(239,68,68,0.6)]">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                            {{ __('LIVE') }}
                        </span>
                    </div>
                    <div class="p-2.5 sm:p-4">
                        <p class="event-tile-name font-semibold text-sm sm:text-base truncate">{{ $item['event']->name }}</p>
                        <p class="text-xs sm:text-sm text-[#8a7a70] truncate">{{ __('Fight #:number', ['number' => $item['fight']->fight_number]) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

<div>
    <h2 class="flex items-center gap-2 text-[11.5px] font-extrabold uppercase tracking-[0.1em] text-[#8a7a70] mb-3">
        <span class="h-[7px] w-[7px] rounded-full bg-[#303a4a]"></span>
        {{ __('Upcoming') }}
    </h2>
    @if ($upcomingEvents->isEmpty())
        <p class="text-[#8a7a70] text-sm">{{ __('No upcoming events scheduled.') }}</p>
    @else
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            @foreach ($upcomingEvents as $event)
                <div class="overflow-hidden gold-panel">
                    <div class="relative aspect-video bg-[#0e131c]">
                        @if ($event->displayBannerUrl())
                            <img src="{{ $event->displayBannerUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover grayscale">
                        @endif
                        <span class="absolute top-2 left-2 rounded-full bg-[#141a2a] px-2 py-0.5 text-[10px] sm:text-[11px] font-bold uppercase text-[#c9baaf]">
                            {{ __('Upcoming') }}
                        </span>
                    </div>
                    <div class="p-2.5 sm:p-4">
                        <p class="font-semibold text-sm sm:text-base truncate">{{ $event->name }}</p>
                        <p class="text-xs sm:text-sm text-[#8a7a70] truncate">{{ $event->arena ?? __('Arena TBA') }}</p>
                        @if ($event->date)
                            <p class="text-xs text-[#6b5c53] mt-1">{{ $event->date->format('M j, g:i A') }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
