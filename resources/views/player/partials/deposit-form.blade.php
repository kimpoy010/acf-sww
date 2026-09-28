{{-- The GCash/Maya deposit form — shared between the standalone Cash In/Out
     page and the Wallet page's Deposit tab (see wallet/index.blade.php).
     Expects $PaybucksChannel, $player, $routePrefix, $topRoutePrefix in scope. --}}
<div id="cash-in" class="rounded-xl bg-[#0a0e16] border border-[#141a2a] p-4">
    <h2 class="font-semibold mb-3">{{ __('Cash in (deposit)') }}</h2>
    <form method="POST" action="{{ route($routePrefix.'deposit') }}" class="space-y-3">
        @csrf
        <div>
            <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Channel') }}</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($PaybucksChannel::CHANNELS as $channel)
                    <label class="deposit-channel-option flex items-center justify-center gap-2 rounded-lg border border-[#141a2a] bg-[#05070b] py-2 cursor-pointer text-sm font-semibold has-[:checked]:border-red-500 has-[:checked]:bg-red-950/40">
                        <input type="radio" name="channel" value="{{ $channel }}" class="deposit-channel-input hidden" data-scope="deposit" data-requires-account="{{ $PaybucksChannel::depositRequiresAccountNumber($channel) ? '1' : '0' }}" {{ $loop->first ? 'checked' : '' }} required>
                        {{ $PaybucksChannel::label($channel) }}
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Amount') }}</label>
            <input type="text" inputmode="decimal" name="amount" required placeholder="{{ __('e.g. :amount', ['amount' => '500']) }}"
                   class="amount-input w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div id="deposit-account-number-field">
            <label class="block text-sm text-[#8a7a70] mb-1">{{ __('GCash number') }}</label>
            <input type="text" inputmode="numeric" name="account_number" placeholder="09XXXXXXXXX"
                   value="{{ old('account_number', $player->gcash_account_number) }}"
                   class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            @unless ($player->gcash_account_number)
                <p class="text-xs text-[#8a7a70] mt-1">{!! __('Tip: save this in :link so it fills in automatically next time.', ['link' => '<a href="'.route($topRoutePrefix.'payment-methods.index').'" class="underline">'.__('Payment methods').'</a>']) !!}</p>
            @endunless
        </div>
        <button class="w-full rounded-lg bg-red-600 hover:bg-red-500 transition font-extrabold py-2 shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">{{ __('Deposit') }}</button>
    </form>
</div>
