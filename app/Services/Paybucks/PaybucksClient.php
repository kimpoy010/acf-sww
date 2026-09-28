<?php

namespace App\Services\Paybucks;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over Paybucks' Merchant API (see the vendor's PDF doc).
 * Every call carries the x-merchant-api-key header (section 5) and raises
 * PaybucksException on anything but a 2xx — callers decide what that
 * means for the CashTransaction they're working with.
 */
class PaybucksClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
    ) {}

    public static function make(): self
    {
        return new self(
            rtrim((string) config('services.paybucks.base_url'), '/'),
            config('services.paybucks.api_key'),
        );
    }

    /**
     * POST /payments/deposits — see doc section 7.
     */
    public function deposit(array $payload): array
    {
        return $this->send('post', '/payments/deposits', $payload);
    }

    /**
     * POST /payments/withdrawals — see doc section 8.
     */
    public function withdraw(array $payload): array
    {
        return $this->send('post', '/payments/withdrawals', $payload);
    }

    /**
     * GET /payments/balance — see doc section 9.
     */
    public function balance(): array
    {
        return $this->send('get', '/payments/balance');
    }

    /**
     * GET /payments/orders/{merchantOrderNo} — see doc section 10. This is
     * the source of truth CashTransactionService::reconcilePaybucksOrder()
     * calls before ever crediting/debiting a wallet — never the inbound
     * callback payload alone, which Paybucks documents no signature for.
     */
    public function orderStatus(string $merchantOrderNo): array
    {
        return $this->send('get', '/payments/orders/'.rawurlencode($merchantOrderNo));
    }

    private function send(string $method, string $path, array $payload = []): array
    {
        if (blank($this->apiKey)) {
            throw new PaybucksException('Paybucks is not configured — set PAYBUCKS_API_KEY.');
        }

        $request = Http::baseUrl($this->baseUrl)
            ->withHeaders(['x-merchant-api-key' => $this->apiKey])
            ->acceptJson()
            ->timeout(20);

        $response = $method === 'get' ? $request->get($path) : $request->{$method}($path, $payload);

        if ($response->failed()) {
            throw new PaybucksException(
                "Paybucks {$method} {$path} failed with HTTP {$response->status()}: {$response->body()}"
            );
        }

        $body = $response->json() ?? [];

        // Every endpoint wraps its actual payload in {success, statusCode,
        // data: {...}} rather than returning it flat, despite the doc's
        // examples showing flat fields — confirmed from a real deposit
        // response (paymentUrl/qrImageUrl/etc. were all under `data`, not
        // top-level, which is why they read as missing before this).
        // success:false is a business-level failure the HTTP status alone
        // doesn't catch (Paybucks can return e.g. 200/201 either way).
        if (array_key_exists('success', $body) && $body['success'] === false) {
            throw new PaybucksException(
                "Paybucks {$method} {$path} reported failure: ".($body['message'] ?? $response->body())
            );
        }

        return $body['data'] ?? $body;
    }
}
