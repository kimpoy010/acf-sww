{{-- Wiring for deposit-form.blade.php / withdraw-form.blade.php — shared
     between the standalone Cash In/Out page and the Wallet page's
     Deposit/Withdraw tabs, so it only needs to be written (and re-run
     after the forms are swapped into view) once. --}}
<script>
    (function () {
        // Toggle the GCash-only account-number field on the deposit form.
        const depositField = document.getElementById('deposit-account-number-field');
        document.querySelectorAll('.deposit-channel-input').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) depositField.classList.toggle('hidden', input.dataset.requiresAccount !== '1');
            });
        });
        const checkedDeposit = document.querySelector('.deposit-channel-input:checked');
        if (checkedDeposit && depositField) depositField.classList.toggle('hidden', checkedDeposit.dataset.requiresAccount !== '1');

        // Show the saved destination for whichever channel is selected, and
        // disable the submit button unless that channel has one saved.
        const withdrawSubmit = document.getElementById('withdraw-submit');
        const showWithdrawDestination = (channel) => {
            document.querySelectorAll('.withdraw-destination').forEach((el) => {
                el.classList.toggle('hidden', el.id !== 'withdraw-destination-' + channel);
            });
            const active = document.getElementById('withdraw-destination-' + channel);
            if (withdrawSubmit && active) {
                withdrawSubmit.disabled = active.dataset.hasAccount !== '1' || withdrawSubmit.dataset.noBalance === '1';
            }
        };
        document.querySelectorAll('.withdraw-channel-input').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) showWithdrawDestination(input.value);
            });
        });
        const checkedWithdraw = document.querySelector('.withdraw-channel-input:checked');
        if (checkedWithdraw) showWithdrawDestination(checkedWithdraw.value);

        // "Max" fills in the full available balance rather than requiring
        // the player to type/copy it themselves.
        const maxBtn = document.getElementById('withdraw-max-btn');
        const amountInput = document.getElementById('withdraw-amount-input');
        if (maxBtn && amountInput) {
            maxBtn.addEventListener('click', () => {
                amountInput.value = Number(maxBtn.dataset.max).toFixed(2);
            });
        }
    })();
</script>
