@extends('layouts.app')

@section('title', __('Site Branding'))

@section('content')
<h1 class="text-2xl font-bold mb-6">{{ __('Site Branding') }}</h1>

<form method="POST" action="{{ route('webmaster.cms.update') }}" enctype="multipart/form-data" class="max-w-lg space-y-6">
    @csrf
    @method('PUT')

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <label class="block font-semibold mb-2">{{ __('Site name') }}</label>
        <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required maxlength="100"
               class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
        <p class="text-sm text-slate-400 mt-2">{{ __('Shown on the login page, the browser tab title, and the top navigation.') }}</p>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <label class="block font-semibold mb-2">{{ __('Logo') }}</label>
        @if ($logoUrl)
            <div class="flex items-center gap-3 mb-3">
                <img src="{{ $logoUrl }}" alt="{{ __('Current logo') }}" class="w-14 h-14 rounded-lg object-cover bg-slate-800 border border-slate-700">
                <label class="flex items-center gap-2 text-sm text-slate-400">
                    <input type="checkbox" name="remove_logo" value="1" class="rounded">
                    {{ __('Remove current logo') }}
                </label>
            </div>
        @endif
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"
               class="w-full text-sm text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-2 file:text-slate-200 hover:file:bg-slate-700">
        <p class="text-sm text-slate-400 mt-2">{{ __('Used as the favicon and next to the site name in the top navigation. PNG/JPG/WEBP/SVG, up to 2 MB.') }}</p>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <label class="block font-semibold mb-2">{{ __('Background image') }}</label>
        @if ($backgroundUrl)
            <div class="flex items-center gap-3 mb-3">
                <img src="{{ $backgroundUrl }}" alt="{{ __('Current background') }}" class="w-24 h-14 rounded-lg object-cover bg-slate-800 border border-slate-700">
                <label class="flex items-center gap-2 text-sm text-slate-400">
                    <input type="checkbox" name="remove_background" value="1" class="rounded">
                    {{ __('Remove current background') }}
                </label>
            </div>
        @endif
        <input type="file" name="background" accept="image/png,image/jpeg,image/webp,image/gif"
               class="w-full text-sm text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-2 file:text-slate-200 hover:file:bg-slate-700">
        <p class="text-sm text-slate-400 mt-2">{{ __('Shown full-page behind every player screen except the live betting page. PNG/JPG/WEBP/GIF (animated GIFs play), up to 4 MB.') }}</p>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <label class="flex items-center justify-between gap-4 cursor-pointer">
            <span>
                <span class="block font-semibold">{{ __('Hide site name in navigation') }}</span>
                <span class="block text-sm text-slate-400 mt-1">{{ __('When on, only the logo shows in the top navigation — the site name still appears on the login page and browser tab title.') }}</span>
            </span>
            <input type="checkbox" name="hide_nav_site_name" value="1" @checked($hideNavSiteName) class="w-5 h-5 rounded shrink-0">
        </label>
    </div>

    <button class="rounded-lg bg-red-600 hover:bg-red-500 transition font-semibold px-4 py-2">{{ __('Save') }}</button>
</form>
@endsection
