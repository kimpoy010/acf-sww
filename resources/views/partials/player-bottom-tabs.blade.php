{{-- Bottom tab bar — the player app is phone-first (see pool-betting.blade.php's
     own full-bleed layout), and the four destinations here cover everything a
     player needs day to day, so they live within thumb's reach at the bottom
     of the screen instead of behind a top-nav dropdown.

     Cash (deposit/withdraw via GCash/Maya — see Player\CashController and
     App\Services\Paybucks) is back here now that it's self-service again;
     it only dropped out while it needed a teller to approve each request,
     and teller accounts are retired. --}}
@php
    $activeTab = match (true) {
        request()->routeIs('play.wallet.*') => 'wallet',
        request()->routeIs('play.cash.*') => 'cash',
        request()->routeIs('play.profile') => 'profile',
        default => 'home',
    };

    $tabs = [
        ['key' => 'home', 'route' => route('play.index'), 'icon' => 'home', 'label' => __('Home')],
        ['key' => 'wallet', 'route' => route('play.wallet.index'), 'icon' => 'wallet', 'label' => __('Wallet')],
        ['key' => 'cash', 'route' => route('play.cash.index'), 'icon' => 'cash', 'label' => __('Cash')],
        ['key' => 'profile', 'route' => route('play.profile'), 'icon' => 'profile', 'label' => __('Profile')],
    ];
@endphp
<nav class="fixed bottom-0 inset-x-0 z-40 bg-[#070b14]/95 backdrop-blur border-t border-[#0c121e]"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="grid grid-cols-4 max-w-lg mx-auto">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['route'] }}"
               class="relative flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-semibold transition
                   {{ $activeTab === $tab['key'] ? 'text-red-400' : 'text-[#8a7a70] hover:text-[#c9baaf]' }}"
               @if ($activeTab === $tab['key']) aria-current="page" @endif>
                @if ($activeTab === $tab['key'])
                    <span class="absolute top-0 left-1/2 -translate-x-1/2 w-6 h-0.5 rounded-full bg-red-500 shadow-[0_0_6px_1px_rgba(239,68,68,0.7)]"></span>
                @endif
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    @switch($tab['icon'])
                        @case('home')
                            <path d="M4 11.5L12 4l8 7.5"/>
                            <path d="M6 10v9a1 1 0 001 1h10a1 1 0 001-1v-9"/>
                            <circle cx="12" cy="15.5" r="1.6" fill="currentColor" stroke="none"/>
                            @break
                        @case('wallet')
                            <rect x="3.5" y="6.5" width="17" height="12" rx="2.5"/>
                            <path d="M7 6.5V5.8a1.8 1.8 0 011.8-1.8h5.6a1.2 1.2 0 011.2 1.2v1.3"/>
                            <circle cx="16.2" cy="12.5" r="1.4" fill="currentColor" stroke="none"/>
                            @break
                        @case('cash')
                            <rect x="2.5" y="7" width="19" height="11" rx="2"/>
                            <circle cx="12" cy="12.5" r="2.3"/>
                            @break
                        @case('profile')
                            <circle cx="12" cy="8.2" r="3.4"/>
                            <path d="M4.8 19.5c1.1-3.6 4-5.6 7.2-5.6s6.1 2 7.2 5.6"/>
                            @break
                    @endswitch
                </svg>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
