<?php

use App\Http\Controllers\Api\PaybucksCallbackController;
use App\Http\Controllers\Api\RfidScanController;
use Illuminate\Support\Facades\Route;

// Called by the local serial bridge process, not a browser — authenticated
// by the terminal's bearer token rather than a logged-in session.
Route::middleware('rfid.terminal')->post('/rfid/scan', [RfidScanController::class, 'store'])->name('api.rfid.scan');

// Paybucks' server-to-server deposit/withdrawal callbacks (see the vendor's
// Merchant API doc, section 12) — configured in the Paybucks portal as
// {APP_URL}/api/paybucks/callback/deposit and .../withdrawal. No auth
// middleware: see PaybucksCallbackController's own doc comment for why
// that's safe (it never trusts the payload for the actual outcome).
Route::prefix('paybucks/callback')->name('api.paybucks.callback.')->group(function () {
    Route::post('/deposit', [PaybucksCallbackController::class, 'deposit'])->name('deposit');
    Route::post('/withdrawal', [PaybucksCallbackController::class, 'withdrawal'])->name('withdrawal');
});
