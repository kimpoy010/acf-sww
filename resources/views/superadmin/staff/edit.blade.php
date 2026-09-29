@extends('layouts.app')

@section('title', __('Edit staff account'))

@section('content')
<h1 class="text-2xl font-bold mb-6">{{ __('Edit Staff Account') }}</h1>

<form method="POST" action="{{ route('superadmin.staff.update', $staffMember) }}" class="max-w-lg space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Role') }}</label>
        <select name="role" required class="w-full gold-input px-3 py-2">
            <option value="declarator" selected>{{ __('Declarator') }}</option>
        </select>
    </div>
    <div>
        <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Full name') }}</label>
        <input name="name" value="{{ old('name', $staffMember->name) }}" required class="w-full gold-input px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Username') }}</label>
        <input name="username" value="{{ old('username', $staffMember->username) }}" required class="w-full gold-input px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('Email') }}</label>
        <input type="email" name="email" value="{{ old('email', $staffMember->email) }}" required class="w-full gold-input px-3 py-2">
    </div>
    <div>
        <label class="block text-sm text-[#9c8f7b] mb-1">{{ __('New password (leave blank to keep current)') }}</label>
        <div class="relative">
            <input type="password" id="staff-edit-password" name="password" class="w-full gold-input px-3 py-2 pr-10">
            @include('partials.password-toggle-button', ['for' => 'staff-edit-password'])
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button class="gold-btn">{{ __('Save changes') }}</button>
        <a href="{{ route('superadmin.staff.index') }}" class="text-sm text-[#9c8f7b] hover:underline">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
