@extends('layouts.app')

@section('title', __('Agents'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">{{ __('Agents') }}</h1>
    <a href="{{ route('superadmin.agents.create') }}" class="gold-btn gold-btn-sm">+ {{ __('New agent') }}</a>
</div>

<div class="gold-panel overflow-x-auto scroll-thin">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[#9c8f7b] border-b border-[#141a2a]">
                <th class="px-4 py-2">{{ __('Agent') }}</th>
                <th>{{ __('Upline') }}</th>
                <th>{{ __('Level') }}</th>
                <th>{{ __('Rate (:game)', ['game' => $game?->display_name]) }}</th>
                <th>{{ __('Downline') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agents as $agent)
                @php $rate = $agent->commissionRates->firstWhere('game_id', $game?->id); @endphp
                <tr class="border-b border-[#141a2a]">
                    <td class="px-4 py-2 font-semibold">{{ $agent->displayName() }}</td>
                    <td class="text-[#9c8f7b]">{{ $agent->agent?->username ?? __('— (top level)') }}</td>
                    <td>
                        <form method="POST" action="{{ route('superadmin.agents.set-level', $agent) }}" class="flex items-center gap-1">
                            @csrf
                            <select name="agent_level_id" onchange="this.form.submit()" class="gold-input rounded px-1 py-0.5 text-xs">
                                <option value="">—</option>
                                @foreach ($agentLevels as $level)
                                    <option value="{{ $level->id }}" @selected($agent->agent_level_id === $level->id)>{{ $level->label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        @if ($game)
                            <form method="POST" action="{{ route('superadmin.agents.set-rate', $agent) }}" class="flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="game_id" value="{{ $game->id }}">
                                <input type="number" step="0.01" min="0" max="100" name="commission_rate"
                                       value="{{ $rate?->commission_rate ?? 0 }}"
                                       class="w-16 gold-input rounded px-1 py-0.5 text-xs">
                                <button class="text-xs hover:underline" style="color:#c9a04a">{{ __('Set') }}</button>
                            </form>
                        @endif
                    </td>
                    <td>{{ $agent->downline_count }}</td>
                    <td class="px-4 text-right text-[#9c8f7b] text-xs">{{ $agent->referral_code }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-[#9c8f7b]">{{ __('No agents yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
