@extends('layouts.app')

@section('title', __('Reset password'))

@section('content')
@include('player.partials.gold-theme-styles')

<div class="max-w-sm mx-auto mt-12">
    <div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
    <p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
</div>

<div class="max-w-sm mx-auto mt-4 gold-card">
    <h1 class="gold-serif" style="font-size:24px;font-weight:600;color:#f4efe4;text-align:center;margin:0 0 20px">{{ __('Reset your password') }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/40 px-4 py-3 text-red-200 text-sm">
            {{ __($errors->first()) }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('New password') }}</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Confirm password') }}</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <button type="submit" class="gold-btn-frame gold-btn-primary">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Reset password') }}</span>
        </button>
    </form>
</div>
@endsection
