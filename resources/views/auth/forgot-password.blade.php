@extends('layouts.app')

@section('title', __('Forgot password'))

@section('content')
@include('player.partials.gold-theme-styles')

<div class="max-w-sm mx-auto mt-12">
    <div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
    <p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
</div>

<div class="max-w-sm mx-auto mt-4 gold-card">
    <h1 class="gold-serif" style="font-size:24px;font-weight:600;color:#f4efe4;text-align:center;margin:0 0 8px">{{ __('Forgot your password?') }}</h1>
    <p class="text-sm text-[#9c8f7b] mb-6 text-center">{{ __("Enter your account's email and we'll send you a link to reset your password.") }}</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/40 px-4 py-3 text-red-200 text-sm">
            {{ __($errors->first()) }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <button type="submit" class="gold-btn-frame gold-btn-primary">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Send reset link') }}</span>
        </button>
    </form>

    <p class="text-sm text-center mt-4">
        <a href="{{ route('login') }}" class="text-[#c9a04a] hover:underline">{{ __('Back to log in') }}</a>
    </p>
</div>
@endsection
