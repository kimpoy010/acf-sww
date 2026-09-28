<div class="gold-panel overflow-x-auto scroll-thin">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-[#9c8f7b] uppercase tracking-wide border-b border-[#141a2a]">
                <th class="px-4 py-3 font-medium">{{ __('Date & time') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Description') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Category') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('Event') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Amount') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Balance after') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#141a2a]">
            @forelse ($transactions ?? [] as $tx)
                @php
                    $isCredit = $tx->type === 'credit';
                    $eventName = in_array($tx->reference_type, \App\Support\WalletTransactionEventNames::BET_LINKED_REFERENCE_TYPES, true)
                        ? ($eventNamesByBetId[$tx->reference_id] ?? null)
                        : null;
                @endphp
                <tr class="hover:bg-white/[0.03] transition">
                    <td class="px-4 py-3 whitespace-nowrap text-[#9c8f7b]">{{ $tx->created_at->format('M j, Y g:i A') }}</td>
                    <td class="px-4 py-3">{{ $tx->description ?: ucfirst(str_replace('_', ' ', $tx->reference_type ?? $tx->type)) }}</td>
                    <td class="px-4 py-3 text-[#9c8f7b] capitalize">{{ str_replace('_', ' ', $tx->reference_type ?? '—') }}</td>
                    <td class="px-4 py-3 text-[#9c8f7b]">{{ $eventName ?? '—' }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold {{ $isCredit ? 'text-emerald-400' : 'text-red-400' }}">
                        {{ $isCredit ? '+' : '-' }}{{ $currencySymbol }}{{ number_format($tx->amount, 2) }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap text-[#9c8f7b]">{{ $currencySymbol }}{{ number_format($tx->balance_after, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-[#9c8f7b]">{{ __('No transactions in this range.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($transactions && $transactions->hasPages())
    <div class="mt-4">
        {{ $transactions->onEachSide(1)->links() }}
    </div>
@endif
