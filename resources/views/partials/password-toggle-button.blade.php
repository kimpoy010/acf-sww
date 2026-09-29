{{-- Expects $for — the id of the password <input> this button toggles.
     See resources/js/password-toggle.js for the click handler; that
     script is bundled globally so this partial needs no page-specific
     script include. --}}
<button type="button" data-toggle-password="{{ $for }}" tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-[#9c8f7b] hover:text-[#f4efe4] transition"
        aria-label="{{ __('Show password') }}">
    <svg class="pw-eye-open w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M1.5 10S4.5 4 10 4s8.5 6 8.5 6-3 6-8.5 6-8.5-6-8.5-6z"/>
        <circle cx="10" cy="10" r="2.5"/>
    </svg>
    <svg class="pw-eye-closed hidden w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M2.5 2.5l15 15"/>
        <path d="M8.4 4.3A8.9 8.9 0 0110 4c5.5 0 8.5 6 8.5 6a13.8 13.8 0 01-2.9 3.8M5.6 5.6C3 7.2 1.5 10 1.5 10s3 6 8.5 6a8.4 8.4 0 003.9-.9"/>
        <path d="M11.8 11.8a2.5 2.5 0 01-3.6-3.6"/>
    </svg>
</button>
