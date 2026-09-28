{{-- GCash/Maya payment-method forms — shared between the standalone
     Payment Methods page and the player Profile page. Expects $player,
     $routePrefix in scope. --}}
<div class="grid md:grid-cols-2 gap-4">
    <div class="gold-panel p-4">
        <h3 class="font-semibold mb-3">{{ __('GCash') }}</h3>
        <form method="POST" action="{{ route($routePrefix.'update') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="channel" value="gcash">
            <div>
                <label class="block text-sm text-[#8a7a70] mb-1">{{ __('GCash number') }}</label>
                <input type="text" inputmode="numeric" name="account_number" required placeholder="09XXXXXXXXX"
                       value="{{ old('account_number', $player->gcash_account_number) }}"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <button type="submit" class="gold-btn-frame gold-btn-primary is-active">
                <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
                <span class="gold-btn-fill">{{ $player->gcash_account_number ? __('Update') : __('Save') }}</span>
            </button>
        </form>
    </div>

    <div class="gold-panel p-4">
        <h3 class="font-semibold mb-3">{{ __('Maya') }}</h3>
        <form method="POST" action="{{ route($routePrefix.'update') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="channel" value="maya">
            <div>
                <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Maya number') }}</label>
                <input type="text" inputmode="numeric" name="account_number" required placeholder="09XXXXXXXXX"
                       value="{{ old('account_number', $player->maya_account_number) }}"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-sm text-[#8a7a70] mb-1">{{ __('Account name') }}</label>
                <input type="text" name="account_name" required placeholder="{{ __('Full name on the account') }}"
                       value="{{ old('account_name', $player->maya_account_name) }}"
                       class="w-full rounded-lg bg-[#05070b] border border-[#141a2a] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <button type="submit" class="gold-btn-frame gold-btn-secondary is-active">
                <span class="gold-btn-corner tl"></span><span class="gold-btn-corner tr"></span><span class="gold-btn-corner bl"></span><span class="gold-btn-corner br"></span>
                <span class="gold-btn-fill">{{ $player->maya_account_number ? __('Update') : __('Save') }}</span>
            </button>
        </form>
    </div>
</div>
