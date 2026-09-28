{{-- Shown in place of the deposit/withdraw forms while a cash request is
     already pending — shared between the standalone Cash In/Out page and
     the Wallet page's Deposit/Withdraw tabs. Expects $pending,
     $PaybucksChannel, $routePrefix, $currencySymbol in scope. --}}
<div class="rounded-xl p-4" style="background:rgba(120,53,15,0.25);border:1px solid rgba(217,119,6,0.5);">
    <p class="font-semibold mb-1">{{ __('You have a pending :type request', ['type' => __(ucfirst($pending->type))]) }}</p>
    <p class="text-sm text-[#c9baaf] mb-3">{{ __(':amount via :channel — waiting for confirmation.', ['amount' => $currencySymbol.number_format($pending->amount, 2), 'channel' => $pending->channel ? $PaybucksChannel::label($pending->channel) : __('cash')]) }}</p>
    <a href="{{ route($routePrefix.'show', $pending) }}" class="inline-block rounded-lg bg-red-600 hover:bg-red-500 transition font-semibold px-4 py-2 text-sm shadow-[0_4px_14px_-4px_rgba(220,38,38,0.6)]">{{ __('View request') }}</a>
</div>
