@extends('layouts.app')

@section('title', __('Staff'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold" style="color:#f4efe4">{{ __('Staff') }}</h1>
    <a href="{{ route('superadmin.staff.create') }}" class="gold-btn gold-btn-sm">+ {{ __('New staff account') }}</a>
</div>

<div class="gold-panel overflow-x-auto scroll-thin">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[#9c8f7b] border-b border-[#141a2a]">
                <th class="px-4 py-2">{{ __('Name') }}</th>
                <th>{{ __('Username') }}</th>
                <th>{{ __('Role') }}</th>
                <th>{{ __('Email') }}</th>
                <th class="px-4">{{ __('Status') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($staff as $user)
                <tr class="border-b border-[#141a2a]">
                    <td class="px-4 py-2 font-semibold">{{ $user->displayName() }}</td>
                    <td class="text-[#9c8f7b]">{{ $user->username }}</td>
                    <td>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-sky-900 text-sky-300">
                            {{ __('Declarator') }}
                        </span>
                    </td>
                    <td class="text-[#9c8f7b]">{{ $user->email }}</td>
                    <td class="px-4">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $user->status === 'active' ? 'bg-[#141a2a] text-[#c9baaf]' : 'bg-red-950 text-red-400' }}">
                            {{ $user->status === 'active' ? __('Active') : __('Inactive') }}
                        </span>
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('superadmin.staff.edit', $user) }}" class="text-sm hover:underline" style="color:#c9a04a">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('superadmin.staff.toggle-status', $user) }}"
                                  onsubmit="return confirm('{{ $user->status === 'active' ? __('Deactivate :username? They will be signed out and unable to log back in until reactivated.', ['username' => $user->username]) : __('Reactivate :username?', ['username' => $user->username]) }}');">
                                @csrf
                                <button class="text-sm {{ $user->status === 'active' ? 'text-amber-400' : 'text-emerald-400' }} hover:underline">
                                    {{ $user->status === 'active' ? __('Deactivate') : __('Reactivate') }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-[#9c8f7b]">{{ __('No declarator accounts yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
