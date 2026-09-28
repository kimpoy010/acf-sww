@extends('layouts.app')

@section('title', __('Log in'))

@section('content')

@if ($cms['logo_url'])
    <div class="max-w-sm mx-auto mt-8 flex justify-center">
        <img src="{{ $cms['logo_url'] }}" alt="{{ $cms['site_name'] }}" class="w-[250px] h-[250px] object-contain">
    </div>
@else
    <div class="max-w-sm mx-auto mt-12">
        <div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
        <p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
    </div>
@endif

<div class="max-w-sm mx-auto {{ $cms['logo_url'] ? 'mt-4' : 'mt-4' }} gold-card">
    <h1 class="gold-serif" style="font-size:24px;font-weight:600;color:#f4efe4;text-align:center;margin:0 0 20px">{{ __('Log in') }}</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-700 bg-red-900/40 px-4 py-3 text-red-200 text-sm">
            {{ __($errors->first()) }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Username or email') }}</label>
            <input type="text" name="login" value="{{ old('login') }}" required autofocus
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Password') }}</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#c9a04a]">
        </div>
        @if (config('services.turnstile.site_key'))
            <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
        @endif
        <button type="submit" class="gold-btn-frame gold-btn-primary">
            <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
            <span class="gold-btn-fill">{{ __('Log in') }}</span>
        </button>
    </form>

    <p class="text-sm text-center mt-4">
        <a href="{{ route('password.request') }}" class="text-[#c9a04a] hover:underline">{{ __('Forgot password?') }}</a>
    </p>

    <p class="text-sm text-[#9c8f7b] mt-4 text-center">
        {{ __('No account?') }} <a href="{{ route('register') }}" class="text-[#c9a04a] hover:underline">{{ __('Register') }}</a>
    </p>
</div>

@if (config('services.turnstile.site_key'))
    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif
@endsection
