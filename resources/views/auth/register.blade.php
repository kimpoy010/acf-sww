@extends('layouts.app')

@section('title', __('Register'))

@section('content')

<div class="max-w-sm mx-auto mt-12">
    <div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
    <p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
</div>

<div class="max-w-sm mx-auto mt-4 gold-card">
    <h1 class="gold-serif" style="font-size:24px;font-weight:600;color:#f4efe4;text-align:center;margin:0 0 20px">{{ __('Create your account') }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/40 px-4 py-3 text-red-200 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ __($error) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($refCode && $refRole === 'agent')
        <div class="mb-4 rounded-lg border border-emerald-700 bg-emerald-900/40 px-4 py-3 text-emerald-200 text-sm">
            {{ __("You're joining as a player under an agent's referral.") }}
        </div>
    @elseif ($refCode && $refRole === 'superadmin')
        <div class="mb-4 rounded-lg border border-sky-700 bg-sky-900/40 px-4 py-3 text-sky-200 text-sm">
            {{ __("You're registering as a new agent.") }}
        </div>
    @elseif ($refCode)
        <div class="mb-4 rounded-lg border border-amber-700 bg-amber-900/40 px-4 py-3 text-amber-200 text-sm">
            {{ __("That referral link isn't recognized — registering as a regular player instead.") }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="ref_code" value="{{ $refCode }}">
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Full name') }}</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Username') }}</label>
            <input type="text" name="username" value="{{ old('username') }}" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Password') }}</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Confirm password') }}</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        @if (config('services.turnstile.site_key'))
            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
        @endif
        <button type="submit" class="gold-btn-frame gold-btn-primary">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Register') }}</span>
        </button>
    </form>

    <p class="text-sm text-[#9c8f7b] mt-4 text-center">
        {{ __('Already have an account?') }} <a href="{{ route('login') }}" class="text-[#c9a04a] hover:underline">{{ __('Log in') }}</a>
    </p>
</div>

@if (config('services.turnstile.site_key'))
    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif
@endsection
