<?php

namespace App\Services\Paybucks;

/**
 * Any non-2xx response, or a missing API key, from the Paybucks API.
 * Caught at the CashTransactionService call sites, which fail the pending
 * CashTransaction they just created rather than leaving it stuck.
 */
class PaybucksException extends \RuntimeException {}
