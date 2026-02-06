<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Интеграция с API ЮKassa (YooKassa).
 * Документация: https://yookassa.ru/developers/using-api/interaction-format
 */
class YooKassaService
{
    private string $apiUrl;
    private string $shopId;
    private string $secretKey;

    public function __construct()
    {
        $config = config('services.yookassa');
        $this->apiUrl = rtrim($config['api_url'] ?? 'https://api.yookassa.ru/v3', '/');
        $this->shopId = (string) ($config['shop_id'] ?? '');
        $this->secretKey = (string) ($config['secret_key'] ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->shopId !== '' && $this->secretKey !== '';
    }

    /**
     * Создать платёж (редирект на страницу ЮKassa, с сохранением способа оплаты для автоплатежей).
     *
     * @param  array{amount: float, return_url: string, description: string, order_id: int}  $params
     * @return array{id: string, status: string, confirmation_url?: string}|array{error: string}
     */
    public function createPayment(array $params): array
    {
        $amount = number_format((float) $params['amount'], 2, '.', '');
        $returnUrl = $params['return_url'] ?? '';
        $description = $params['description'] ?? 'Оплата подписки';
        $orderId = (int) ($params['order_id'] ?? 0);

        $body = [
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB',
            ],
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'description' => mb_substr($description, 0, 128),
            'metadata' => [
                'order_id' => (string) $orderId,
            ],
            'save_payment_method' => true,
        ];

        $response = $this->request('POST', '/payments', $body);

        if (isset($response['id'], $response['status'])) {
            return [
                'id' => $response['id'],
                'status' => $response['status'],
                'confirmation_url' => $response['confirmation']['confirmation_url'] ?? null,
            ];
        }

        return [
            'error' => $response['description'] ?? $response['code'] ?? 'Unknown YooKassa error',
        ];
    }

    /**
     * Получить информацию о платеже.
     *
     * @return array{id: string, status: string, paid: bool, payment_method?: array}|array{error: string}
     */
    public function getPayment(string $paymentId): array
    {
        $response = $this->request('GET', '/payments/' . $paymentId);

        if (isset($response['id'], $response['status'])) {
            return [
                'id' => $response['id'],
                'status' => $response['status'],
                'paid' => (bool) ($response['paid'] ?? false),
                'payment_method' => $response['payment_method'] ?? null,
                'metadata' => $response['metadata'] ?? [],
            ];
        }

        return [
            'error' => $response['description'] ?? $response['code'] ?? 'Unknown YooKassa error',
        ];
    }

    /**
     * Создать автоплатёж по сохранённому способу оплаты (payment_method_id).
     *
     * @return array{id: string, status: string}|array{error: string}
     */
    public function createRecurringPayment(string $paymentMethodId, float $amount, string $description): array
    {
        $body = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'RUB',
            ],
            'capture' => true,
            'payment_method_id' => $paymentMethodId,
            'description' => mb_substr($description, 0, 128),
        ];

        $response = $this->request('POST', '/payments', $body);

        if (isset($response['id'], $response['status'])) {
            return [
                'id' => $response['id'],
                'status' => $response['status'],
                'paid' => (bool) ($response['paid'] ?? false),
            ];
        }

        return [
            'error' => $response['description'] ?? $response['code'] ?? 'Unknown YooKassa error',
        ];
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $url = $this->apiUrl . $path;
        $auth = base64_encode($this->shopId . ':' . $this->secretKey);

        $headers = [
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/json',
            'Idempotence-Key' => Str::uuid()->toString(),
        ];

        if ($method === 'GET') {
            $response = Http::withHeaders($headers)->get($url);
        } else {
            $response = Http::withHeaders($headers)->post($url, $body);
        }

        $data = $response->json();
        if (! is_array($data)) {
            return ['error' => 'Invalid response from YooKassa', 'http_status' => $response->status()];
        }

        return $data;
    }
}
