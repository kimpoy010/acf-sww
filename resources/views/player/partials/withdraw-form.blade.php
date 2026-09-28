{{-- The GCash/Maya withdrawal form — shared between the standalone Cash
     In/Out page and the Wallet page's Withdraw tab (see wallet/index.blade.php).
     Expects $PaybucksChannel, $player, $wallet, $routePrefix, $topRoutePrefix,
     $withdrawalFee, $currencySymbol in scope. --}}
@php $maxWithdrawable = max(0, ($wallet->availableBalance() ?? 0) - $withdrawalFee); @endphp
<div id="cash-out" class="rounded-xl bg-[#0a0e16] border border-[#141a2a] p-4">
    <h2 class="font-semibold mb-3">{{ __('Cash out (withdraw)') }}</h2>
    <p class="text-sm text-[#8a7a70] mb-1">{{ __('Available balance: :amount', ['amount' => $currencySymbol.number_format($wallet->availableBalance() ?? 0, 2)]) }}</p>
    @if ($withdrawalFee > 0)
        <p class="text-sm text-[#8a7a70] mb-3">{{ __('A :fee fee applies on top of whatever you withdraw.', ['fee' => $currencySymbol.number_format($withdrawalFee, 2)]) }}</p>
    @endif
    <form method="POST" action="{{ route($routePrefix.'withdraw') }}" class="space-y-3">
        @csrf
        <div>
            <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Amount') }}</label>
            <div class="flex gap-2">
                <input type="text" inputmode="decimal" name="amount" id="withdraw-amount-input" required
                       value="{{ old('amount') }}" placeholder="{{ __('e.g. :amount', ['amount' => '500']) }}"
                       class="amount-input w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                <button type="button" id="withdraw-max-btn" data-max="{{ $maxWithdrawable }}"
                        class="shrink-0 rounded-lg bg-white/[0.06] border border-white/[0.14] hover:bg-white/[0.1] transition text-sm font-semibold px-3">{{ __('Max') }}</button>
            </div>
        </div>
        <div>
            <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Channel') }}</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($PaybucksChannel::CHANNELS as $channel)
                    <label class="withdraw-channel-option flex items-center justify-center gap-2 rounded-lg border border-[#141a2a] bg-[#05070b] py-2 cursor-pointer text-sm font-semibold has-[:checked]:border-red-500 has-[:checked]:bg-red-950/40">
                        <input type="radio" name="channel" value="{{ $channel }}" class="withdraw-channel-input hidden" data-scope="withdraw" data-requires-account-name="{{ $PaybucksChannel::withdrawalRequiresAccountName($channel) ? '1' : '0' }}" {{ $loop->first ? 'checked' : '' }} required>
                        {{ $PaybucksChannel::label($channel) }}
                    </label>
                @endforeach
            </div>
        </div>
        @foreach ($PaybucksChannel::CHANNELS as $channel)
            @php $hasSaved = $player->hasSavedPaymentMethod($channel); @endphp
            <div id="withdraw-destination-{{ $channel }}" class="withdraw-destination {{ $loop->first ? '' : 'hidden' }}" data-has-account="{{ $hasSaved ? '1' : '0' }}">
                @if ($hasSaved)
                    <div class="rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 text-sm">
                        <p class="text-[#8a7a70] text-xs mb-0.5">{{ __('Sending to') }}</p>
                        <p class="font-semibold">{{ $player->savedAccountNumber($channel) }}{{ $player->savedAccountName($channel) ? ' — '.$player->savedAccountName($channel) : '' }}</p>
                    </div>
                @else
                    <div class="rounded-lg px-3 py-2 text-sm" style="background:rgba(120,53,15,0.2);border:1px solid rgba(217,119,6,0.4);">
                        {!! __('No :channel account saved yet — add one in :link first.', ['channel' => $PaybucksChannel::label($channel), 'link' => '<a href="'.route($topRoutePrefix.'payment-methods.index').'" class="underline font-semibold">'.__('Payment methods').'</a>']) !!}
                    </div>
                @endif
            </div>
        @endforeach
        <button id="withdraw-submit" type="submit" class="w-full rounded-lg bg-white/[0.06] border border-white/[0.14] hover:bg-white/[0.1] transition font-extrabold py-2"
                data-no-balance="{{ $maxWithdrawable < 20 ? '1' : '0' }}"
                {{ $maxWithdrawable < 20 || ! $player->hasSavedPaymentMethod($PaybucksChannel::CHANNELS[0]) ? 'disabled' : '' }}>
            {{ __('Withdraw') }}
        </button>
    </form>
</div>
