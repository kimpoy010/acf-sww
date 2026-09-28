@extends('layouts.app')

@section('title', __('Teller Cash Flow'))

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-2xl font-bold">{{ __('Teller Cash Flow — Ticket Betting') }}</h1>
    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.reports.teller-cash-flow.export', request()->query()) }}" class="text-sm hover:underline" style="color:#c9a04a">{{ __('Export CSV') }}</a>
        <a href="{{ route('superadmin.dashboard') }}" class="text-sm text-[#9c8f7b] hover:text-white transition">{{ __('Back to dashboard') }}</a>
    </div>
</div>

<p class="text-sm text-[#9c8f7b] mb-6">
    {{ __('Cash each teller has taken in writing over-the-counter tickets, and paid out redeeming winners — separate from wallet deposits/withdrawals, which show on the shift report instead.') }}
</p>

<form method="GET" class="gold-panel p-4 mb-6 flex flex-wrap items-end gap-3">
    @include('partials.date-range-field', ['id' => 'cash-flow-date', 'fromName' => 'from', 'toName' => 'to', 'fromValue' => $filters['from'], 'toValue' => $filters['to']])
    <button class="gold-btn gold-btn-sm">{{ __('Filter') }}</button>
    @if ($filters['from'] || $filters['to'])
        <a href="{{ route('superadmin.reports.teller-cash-flow') }}" class="text-sm text-[#9c8f7b] hover:text-white transition px-2 py-2">{{ __('Clear') }}</a>
    @endif
</form>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="gold-panel p-4">
        <p class="text-xs text-[#9c8f7b] mb-1 uppercase tracking-wide">{{ __('Stakes collected') }}</p>
        <p class="text-xl font-extrabold">{{ $currencySymbol }}{{ number_format($summary->stakes_collected, 2) }}</p>
        <p class="text-xs text-[#9c8f7b] mt-1">{{ __(':count tickets', ['count' => number_format($summary->tickets_written)]) }}</p>
    </div>
    <div class="gold-panel p-4">
        <p class="text-xs text-[#9c8f7b] mb-1 uppercase tracking-wide">{{ __('Payouts paid') }}</p>
        <p class="text-xl font-extrabold">{{ $currencySymbol }}{{ number_format($summary->payouts_paid, 2) }}</p>
        <p class="text-xs text-[#9c8f7b] mt-1">{{ __(':count redeemed', ['count' => number_format($summary->tickets_redeemed)]) }}</p>
    </div>
    <div class="gold-panel p-4">
        <p class="text-xs text-[#9c8f7b] mb-1 uppercase tracking-wide">{{ __('Net cash flow') }}</p>
        <p class="text-xl font-extrabold {{ $summary->net_cash_flow >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $currencySymbol }}{{ number_format($summary->net_cash_flow, 2) }}</p>
        <p class="text-xs text-[#9c8f7b] mt-1">{{ __(':count voided', ['count' => number_format($summary->tickets_voided)]) }}</p>
    </div>
</div>

<div class="gold-panel overflow-x-auto scroll-thin">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-[#9c8f7b] uppercase tracking-wide border-b border-[#141a2a]">
                <th class="px-4 py-3 font-medium">{{ __('Teller') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Written') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Collected') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Redeemed') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Paid out') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Voided') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ __('Net cash flow') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#141a2a]">
            @forelse ($rows as $row)
                <tr class="hover:bg-white/[0.03] transition">
                    <td class="px-4 py-3 whitespace-nowrap font-semibold">{{ $row->teller->displayName() }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->tickets_written) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">{{ $currencySymbol }}{{ number_format($row->stakes_collected, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->tickets_redeemed) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">{{ $currencySymbol }}{{ number_format($row->payouts_paid, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($row->tickets_voided) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold {{ $row->net_cash_flow >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $currencySymbol }}{{ number_format($row->net_cash_flow, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-[#9c8f7b]">{{ __('No ticket activity in this period.') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr class="border-t border-[#141a2a] font-semibold">
                    <td class="px-4 py-3">{{ __('Total') }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($summary->tickets_written) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">{{ $currencySymbol }}{{ number_format($summary->stakes_collected, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($summary->tickets_redeemed) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">{{ $currencySymbol }}{{ number_format($summary->payouts_paid, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($summary->tickets_voided) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap {{ $summary->net_cash_flow >= 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $currencySymbol }}{{ number_format($summary->net_cash_flow, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
@endsection
