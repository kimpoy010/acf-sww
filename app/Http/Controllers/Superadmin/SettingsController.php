<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $balancerSwitch = Setting::get('balancer_switch', 'off');
        $withdrawalFee = Setting::get('withdrawal_fee', '0');

        return view('superadmin.settings.edit', compact('balancerSwitch', 'withdrawalFee'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'withdrawal_fee' => 'required|numeric|min:0',
        ]);

        $old = Setting::get('balancer_switch', 'off');
        $new = $request->boolean('balancer_switch') ? 'on' : 'off';
        Setting::set('balancer_switch', $new);

        $oldFee = Setting::get('withdrawal_fee', '0');
        $newFee = (string) $data['withdrawal_fee'];
        Setting::set('withdrawal_fee', $newFee);

        AuditLogger::log(
            action: 'settings.updated',
            description: __('Payout balancer switched :new; withdrawal fee set to :fee.', ['new' => $new, 'fee' => $newFee]),
            changes: [
                'balancer_switch' => ['old' => $old, 'new' => $new],
                'withdrawal_fee' => ['old' => $oldFee, 'new' => $newFee],
            ],
        );

        return redirect()->route('superadmin.settings.edit')->with('success', __('Settings updated.'));
    }
}
