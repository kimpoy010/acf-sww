@extends('layouts.app')

@section('title', __('Change password'))

@section('content')
<div class="gold-ornament"><span class="line"></span><span class="diamond"></span><span class="line r"></span></div>
<p class="gold-eyebrow">{{ $cms['site_name'] }}</p>
<h1 class="gold-serif gold-h1">{{ __('Change Password') }}</h1>

<div class="max-w-sm mx-auto">
    @if (session('success'))
        <div class="rounded-xl p-3 mb-4 text-sm" style="background:rgba(6,78,59,0.25);border:1px solid rgba(16,185,129,0.4);">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl p-3 mb-4 text-sm" style="background:rgba(120,20,20,0.25);border:1px solid rgba(220,38,38,0.5);">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ __($error) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('account.password.update') }}" class="gold-panel p-4 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('New password') }}</label>
            <div class="relative">
                <input type="password" id="account-password" name="password" required autocomplete="new-password"
                       class="w-full gold-input px-3 py-2 pr-10">
                @include('partials.password-toggle-button', ['for' => 'account-password'])
            </div>
            @include('partials.password-strength-meter', ['passwordField' => 'account-password', 'usernameValue' => auth()->user()->username])
        </div>
        <div>
            <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Confirm new password') }}</label>
            <div class="relative">
                <input type="password" id="account-password-confirmation" name="password_confirmation" required autocomplete="new-password"
                       class="w-full gold-input px-3 py-2 pr-10">
                @include('partials.password-toggle-button', ['for' => 'account-password-confirmation'])
            </div>
        </div>
        <button type="submit" class="gold-btn w-full">{{ __('Update password') }}</button>
    </form>
</div>
@endsection
