@extends('layouts.app')

@section('title', __('Roles & Permissions'))

@section('content')
<h1 class="text-2xl font-bold mb-2">{{ __('Roles & Permissions') }}</h1>
<p class="text-sm text-[#9c8f7b] mb-6 max-w-2xl">
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
                <th class="sticky left-0 bg-[#05070b] text-left text-xs font-semibold text-[#9c8f7b] uppercase px-3 py-2 whitespace-nowrap">{{ __('Role') }}</th>
                @foreach ($labels as $name => $label)
                    <th class="text-center text-xs font-semibold text-[#9c8f7b] px-3 py-2 whitespace-nowrap" title="{{ $name }}">{{ __($label) }}</th>
                @endforeach
                <th class="px-3 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $role)
                @php $rolePermissions = $role->permissions->pluck('name')->all(); @endphp
                <tr class="border-t border-[#141a2a]">
                    <td class="sticky left-0 bg-[#05070b] px-3 py-2.5 font-semibold capitalize whitespace-nowrap">{{ $role->name }}</td>
                    @foreach ($labels as $name => $label)
                        <td class="text-center px-3 py-2.5">
                            <input type="checkbox" form="role-form-{{ $role->id }}" name="permissions[]" value="{{ $name }}"
                                   {{ in_array($name, $rolePermissions, true) ? 'checked' : '' }}
                                   class="rounded">
                        </td>
                    @endforeach
                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                        <button form="role-form-{{ $role->id }}" data-role-name="{{ $role->name }}" data-original-permissions="{{ implode(',', $rolePermissions) }}" class="save-role-btn gold-btn gold-btn-sm">{{ __('Save') }}</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    // Confirm before a save actually removes access — the checkboxes are
    // the whole UI here, so an accidental uncheck-and-save (or a save with
    // literally nothing checked) would otherwise silently strip a role's
    // access with no undo. This only interrupts a save that would remove
    // something already granted; adding permissions never prompts.
    document.querySelectorAll('.save-role-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const form = document.getElementById(btn.getAttribute('form'));
            if (!form) return;

            const roleName = btn.dataset.roleName;
            const before = new Set(btn.dataset.originalPermissions ? btn.dataset.originalPermissions.split(',') : []);
            const after = new Set(Array.from(form.querySelectorAll('input[name="permissions[]"]:checked')).map((cb) => cb.value));
            const removed = Array.from(before).filter((name) => !after.has(name));

            if (removed.length === 0) return;

            const message = after.size === 0
                ? @json(__('This removes ALL admin access from ":role" — nobody with that role will be able to reach any admin section afterward. Continue?'))
                : @json(__('This removes ":permissions" access from ":role". Continue?')).replace(':permissions', removed.join(', '));

            if (!confirm(message.replace(':role', roleName))) {
                e.preventDefault();
            }
        });
    });
</script>
@endsection
