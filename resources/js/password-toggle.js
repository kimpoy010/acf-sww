// Delegated so it works for every `data-toggle-password` button on the
// page, including ones inside content swapped in later (AJAX rows, modals)
// — no per-field wiring needed, just the markup convention below.
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;

    const input = document.getElementById(btn.dataset.togglePassword);
    if (!input) return;

    const showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';

    btn.querySelector('.pw-eye-open')?.classList.toggle('hidden', showing);
    btn.querySelector('.pw-eye-closed')?.classList.toggle('hidden', !showing);
    btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
});
