<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\VipTier;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The VIP rebate tier ladder — how much lifetime "valid" (matched)
 * wagering it takes a player to reach each tier, and what percentage of
 * every matched bet they earn back once there. See VipTier::forValidBets()
 * for how a player's current tier is resolved, and VipRebateService for
 * where the rebate is actually credited.
 */
class VipTierController extends Controller
{
    public function index(): View
    {
        $vipTiers = VipTier::orderBy('min_valid_bets')->get();

        return view('superadmin.vip-tiers.index', compact('vipTiers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'min_valid_bets' => 'required|numeric|min:0',
            'max_valid_bets' => 'nullable|numeric|gte:min_valid_bets',
            'rebate_percent' => 'required|numeric|min:0|max:100',
        ]);

        $tier = VipTier::create($data);

        AuditLogger::log(
            action: 'vip_tier.created',
            description: __('VIP tier :name created.', ['name' => $tier->name]),
            target: $tier,
        );

        return redirect()->route('superadmin.vip-tiers.index')->with('success', __('VIP tier added.'));
    }

    public function update(Request $request, VipTier $vipTier): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'min_valid_bets' => 'required|numeric|min:0',
            'max_valid_bets' => 'nullable|numeric|gte:min_valid_bets',
            'rebate_percent' => 'required|numeric|min:0|max:100',
        ]);

        $vipTier->update($data);

        AuditLogger::log(
            action: 'vip_tier.updated',
            description: __('VIP tier :name updated.', ['name' => $vipTier->name]),
            target: $vipTier,
        );

        return redirect()->route('superadmin.vip-tiers.index')->with('success', __('VIP tier updated.'));
    }

    public function destroy(VipTier $vipTier): RedirectResponse
    {
        $name = $vipTier->name;
        $vipTier->delete();

        AuditLogger::log(
            action: 'vip_tier.deleted',
            description: __('VIP tier :name deleted.', ['name' => $name]),
        );

        return redirect()->route('superadmin.vip-tiers.index')->with('success', __('VIP tier deleted.'));
    }
}
