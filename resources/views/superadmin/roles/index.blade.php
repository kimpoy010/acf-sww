@extends('layouts.app')

@section('title', __('Roles & Permissions'))

@section('content')
<h1 class="text-2xl font-bold mb-2">{{ __('Roles & Permissions') }}</h1>
<p class="text-sm text-slate-400 mb-6 max-w-2xl">
    {{ __('Which admin sections each role can reach. Unchecking a box takes effect immediately for everyone with that role.') }}
</p>

{{-- One <form> per role, kept outside the table entirely (a <form>
     isn't valid flow content inside <table>/<tbody> — a browser would
     foster-parent it out anyway) and wired to each row's checkboxes/
     button purely via the form="" attribute, which associates by id
     regardless of DOM position. --}}
@foreach ($roles as $role)
    <form method="POST" action="{{ route('superadmin.roles.update', $role) }}" id="role-form-{{ $role->id }}">
        @csrf
        @method('PUT')
    </form>
@endforeach

<div class="overflow-x-auto">
    <table class="min-w-full border-separate" style="border-spacing: 0;">
        <thead>
            <tr>
                <th class="sticky left-0 bg-slate-950 text-left text-xs font-semibold text-slate-400 uppercase px-3 py-2 whitespace-nowrap">{{ __('Role') }}</th>
                @foreach ($labels as $name => $label)
                    <th class="text-center text-xs font-semibold text-slate-400 px-3 py-2 whitespace-nowrap" title="{{ $name }}">{{ __($label) }}</th>
                @endforeach
                <th class="px-3 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $role)
                @php $rolePermissions = $role->permissions->pluck('name')->all(); @endphp
                <tr class="border-t border-slate-800">
                    <td class="sticky left-0 bg-slate-950 px-3 py-2.5 font-semibold capitalize whitespace-nowrap">{{ $role->name }}</td>
                    @foreach ($labels as $name => $label)
                        <td class="text-center px-3 py-2.5">
                            <input type="checkbox" form="role-form-{{ $role->id }}" name="permissions[]" value="{{ $name }}"
                                   {{ in_array($name, $rolePermissions, true) ? 'checked' : '' }}
                                   class="rounded">
                        </td>
                    @endforeach
                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                        <button form="role-form-{{ $role->id }}" class="rounded-lg bg-red-600 hover:bg-red-500 transition font-semibold px-3 py-1.5 text-xs">{{ __('Save') }}</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
