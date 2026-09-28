@extends('layouts.app')

@section('title', __('Profile'))

@section('content')
<div class="max-w-sm mx-auto">
    <div class="flex items-center gap-3 mb-4">
        <span class="w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center text-lg font-bold uppercase shrink-0">
            {{ Str::substr($player->displayName(), 0, 1) }}
        </span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold truncate">{{ $player->displayName() }}</h1>
            <p class="text-xs text-slate-500">{{ __('Profile') }}</p>
        </div>
    </div>

    <a href="{{ route('account.password.edit') }}" class="block w-full text-center rounded-xl bg-slate-900 border border-slate-800 hover:border-red-600 hover:text-red-400 transition font-semibold py-3 text-sm mb-4">{{ __('Change password') }}</a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="w-full rounded-xl bg-slate-900 border border-slate-800 hover:border-red-600 hover:text-red-400 transition font-semibold py-3 text-sm">{{ __('Logout') }}</button>
    </form>
</div>
@endsection
