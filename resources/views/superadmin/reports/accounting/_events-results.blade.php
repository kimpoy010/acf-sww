<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="gold-panel p-4">
        <p class="text-xs text-[#9c8f7b] mb-1 uppercase tracking-wide">{{ __('Total bets') }}</p>
        <p class="text-xl font-extrabold">{{ number_format($summary->bet_count) }}</p>
    </div>
    <div class="gold-panel p-4">
        <p class="text-xs text-[#9c8f7b] mb-1 uppercase tracking-wide">{{ __('Total staked') }}</p>
        <p class="text-xl font-extrabold">{{ $currencySymbol }}{{ number_format($summary->staked, 2) }}</p>
    </div>
    <div class="gold-panel p-4">
        <p class="text-xs text-[#9c8f7b] mb-1 uppercase tracking-wide">{{ __('Net income') }}</p>
        @php $netTotal = $summary->staked - $summary->paid_out; @endphp
        <p class="text-xl font-extrabold {{ $netTotal >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $currencySymbol }}{{ number_format($netTotal, 2) }}</p>
    </div>
</div>

<div class="gold-panel overflow-x-auto scroll-thin">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-[#9c8f7b] uppercase tracking-wide border-b border-[#141a2a]">
                <th class="px-4 py-3 font-medium">{{ __('Event') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Fights') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Bets') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Staked') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Paid out') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Net income') }}</th>
                <th class="px-4 py-3 font-medium"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#141a2a]">
            @forelse ($events as $event)
                @php $net = (float) $event->staked - (float) $event->paid_out; @endphp
                <tr class="hover:bg-white/[0.03] transition">
                    <td class="px-4 py-3 whitespace-nowrap font-semibold">{{ $event->name }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-[#9c8f7b]">{{ $event->date?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($event->fight_count) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($event->bet_count) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">{{ $currencySymbol }}{{ number_format($event->staked, 2) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">{{ $currencySymbol }}{{ number_format($event->paid_out, 2) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold {{ $net >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $currencySymbol }}{{ number_format($net, 2) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('superadmin.reports.income', ['event_id' => $event->id]) }}" class="hover:underline" style="color:#c9a04a">{{ __('View fights') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-6 text-center text-[#9c8f7b]">{{ __('No settled events yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($events->hasPages())
        <div class="p-4 border-t border-[#141a2a]">
            {{ $events->links() }}
        </div>
    @endif
</div>
