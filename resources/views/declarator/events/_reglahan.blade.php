{{-- Reglahan — the same Big Road win/loss board players see on the
     betting page, so the declarator can read the trend at a glance
     instead of switching tabs. Rendered both on the initial page load
     and, separately from the fights panel, on every AJAX refresh (see
     EventController::fightsPanel() and refreshPanel() in show.blade.php)
     so it stays live under the video without being nested inside the
     fight-controls column. --}}
@php
    // Same decimal-ization pool-betting.blade.php does for its own
    // Reglahan legend circles — GameTheme's 'hex' is already
    // region-correct (blue for Philippines' Wala, green for Mexico's).
    $hexToRgb = fn (string $hex) => implode(',', sscanf($hex, '#%02x%02x%02x'));
    $meronRgb = $hexToRgb($theme['meron']['hex']);
    $walaRgb = $hexToRgb($theme['wala']['hex']);
@endphp
<div class="gold-panel p-3">
    <p class="font-bold text-sm mb-3" style="color:#e0793a;">{{ __('REGLAHAN') }}</p>

    <div class="flex flex-wrap justify-center gap-x-3 gap-y-1.5 mb-3">
        <span class="flex items-center gap-1.5 text-[11px] text-[#c9baaf]">
            <span class="w-5 h-5 rounded-full shrink-0 flex items-center justify-center text-[9px] font-bold text-white" style="background:rgb({{ $meronRgb }});">{{ $stats['meron'] }}</span>
            {{ strtoupper($event->label_meron) }}
        </span>
        <span class="flex items-center gap-1.5 text-[11px] text-[#c9baaf]">
            <span class="w-5 h-5 rounded-full shrink-0 flex items-center justify-center text-[9px] font-bold text-white" style="background:rgb({{ $walaRgb }});">{{ $stats['wala'] }}</span>
            {{ strtoupper($event->label_wala) }}
        </span>
        <span class="flex items-center gap-1.5 text-[11px] text-[#c9baaf]">
            <span class="w-5 h-5 rounded-full shrink-0 flex items-center justify-center text-[9px] font-bold text-[#1e293b]" style="background:#cbd5e1;">{{ $stats['draw'] }}</span>
            {{ strtoupper($event->label_draw) }}
        </span>
        <span class="flex items-center gap-1.5 text-[11px] text-[#c9baaf]">
            <span class="w-5 h-5 rounded-full shrink-0 flex items-center justify-center text-[9px] font-bold text-white" style="background:#52525b;">{{ $stats['cancelled'] }}</span>
            {{ __('Cancelled') }}
        </span>
    </div>

    <div class="overflow-x-auto scroll-thin">
        <div class="grid gap-1" style="grid-auto-flow: column; grid-template-rows: repeat({{ $reglahan['maxRows'] }}, minmax(0, 1fr)); width: max-content;">
            @forelse ($reglahan['columns'] as $column)
                @foreach ($column as $cell)
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-[#1b243a] flex items-center justify-center text-xs font-bold
                        {{ $cell
                            ? ($cell->winner === 'meron' ? $theme['meron']['reglahan'] : ($cell->winner === 'wala' ? $theme['wala']['reglahan'] : ($cell->winner === 'draw' ? 'bg-[#cbd5e1] text-[#1e293b]' : 'bg-[#52525b] text-[#e2e8f0]')))
                            : 'bg-[#05070b] text-[#303a4a] border-dashed' }}">
                        {{ $cell?->fight_number }}
                    </div>
                @endforeach
            @empty
                @for ($p = 0; $p < 9 * $reglahan['maxRows']; $p++)
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full border border-dashed border-[#141a2a] bg-[#05070b]"></div>
                @endfor
            @endforelse
        </div>
    </div>
</div>
