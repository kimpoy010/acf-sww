<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Services\CashTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives Paybucks' deposit/withdrawal webhooks (see the vendor's
 * Merchant API doc, sections 12-14). Unauthenticated by design — a
 * server-to-server callback from Paybucks, not a logged-in player — so it
 * never trusts the payload for the actual outcome (see
 * CashTransactionService::reconcilePaybucksOrder(), which re-asks Paybucks
 * itself via the Order Status API before touching any wallet). This
 * controller's only job is: find which order the callback is about, ask
 * to reconcile it, and always send back the exact acknowledgment Paybucks
 * requires so it stops retrying.
 */
class PaybucksCallbackController extends Controller
{
    public function __construct(private CashTransactionService $cashService) {}

    public function deposit(Request $request): JsonResponse
    {
        return $this->handle($request);
    }

    public function withdrawal(Request $request): JsonResponse
    {
        return $this->handle($request);
    }

    private function handle(Request $request): JsonResponse
    {
        try {
            $merchantOrderNo = $request->input('merchantOrderNo') ?? $request->input('merchant_order_no');

            $transaction = $merchantOrderNo
                ? CashTransaction::where('code', $merchantOrderNo)->where('provider', 'paybucks')->first()
                : null;

            if ($transaction) {
                $this->cashService->reconcilePaybucksOrder($transaction);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        // Required ack shape (doc section 14) regardless of outcome above —
        // an empty body, HTML, or any other status is treated as failure
        // and triggers Paybucks' retry-with-backoff, which is fine (the
        // next attempt just re-reconciles, a no-op once already settled)
        // but not something to invite by accident.
        return response()->json(['status' => '1', 'error_msg' => '']);
    }
}
