<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    private bool $recurringEnabled;

    public function __construct()
    {
        $config = config('services.yookassa');
        $this->apiUrl = rtrim($config['api_url'] ?? 'https://api.yookassa.ru/v3', '/');
        $this->shopId = (string) ($config['shop_id'] ?? '');
        $this->secretKey = (string) ($config['secret_key'] ?? '');
        $this->recurringEnabled = (bool) ($config['recurring_enabled'] ?? false);
    }

    public function isConfigured(): bool
    {
        return $this->shopId !== '' && $this->secretKey !== '';
    }

    /**
     * Создать платёж (редирект на страницу ЮKassa, с сохранением способа оплаты для автоплатежей).
     * Чек (receipt) обязателен для передачи в ФНС по 54-ФЗ.
     *
     * @param  array{amount: float, return_url: string, description: string, order_id: int, customer_email?: string, customer_phone?: string}  $params
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
            'receipt' => $this->buildReceipt($amount, $description, $params),
        ];
        if ($this->recurringEnabled) {
            $body['save_payment_method'] = true;
        }

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
     * Чек обязателен; передайте customer_email/customer_phone в $receiptParams.
     *
     * @param  array{customer_email?: string, customer_phone?: string, customer_name?: string}  $receiptParams
     * @return array{id: string, status: string}|array{error: string}
     */
    public function createRecurringPayment(string $paymentMethodId, float $amount, string $description, array $receiptParams = []): array
    {
        $amountStr = number_format($amount, 2, '.', '');
        $body = [
            'amount' => [
                'value' => $amountStr,
                'currency' => 'RUB',
            ],
            'capture' => true,
            'payment_method_id' => $paymentMethodId,
            'description' => mb_substr($description, 0, 128),
            'receipt' => $this->buildReceipt($amountStr, $description, $receiptParams),
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

    /**
     * Включены ли рекуррентные платежи (сохранение способа оплаты для автопродления).
     */
    public function isRecurringEnabled(): bool
    {
        return $this->recurringEnabled;
    }

    /**
     * Собрать объект чека для 54-ФЗ (обязателен при приёме платежей).
     * customer: email или phone (хотя бы одно), items: одна позиция, internet: true.
     *
     * @param  string  $amount  Сумма в формате "123.45"
     * @param  string  $description  Описание позиции (до 128 символов)
     * @param  array{customer_email?: string, customer_phone?: string, customer_name?: string}  $params
     */
    private function buildReceipt(string $amount, string $description, array $params): array
    {
        $customer = [];
        if (! empty($params['customer_email'])) {
            $customer['email'] = mb_substr(trim((string) $params['customer_email']), 0, 254);
        }
        if (! empty($params['customer_phone'])) {
            $phone = preg_replace('/\D/', '', (string) $params['customer_phone']);
            if (strlen($phone) >= 10) {
                $customer['phone'] = (strlen($phone) === 10 ? '7' : '') . $phone;
            }
        }
        if (! empty($params['customer_name'])) {
            $customer['full_name'] = mb_substr(trim((string) $params['customer_name']), 0, 256);
        }
        if (empty($customer['email']) && empty($customer['phone'])) {
            $customer['email'] = 'noreply@example.com';
        }

        $receipt = [
            'customer' => $customer,
            'items' => [
                [
                    'description' => mb_substr($description, 0, 128),
                    'quantity' => '1.000',
                    'amount' => [
                        'value' => $amount,
                        'currency' => 'RUB',
                    ],
                    'vat_code' => 1,
                    'payment_mode' => 'full_prepayment',
                    'payment_subject' => 'service',
                ],
            ],
            'internet' => true,
        ];

        return $receipt;
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $url = $this->apiUrl . $path;
        $auth = base64_encode($this->shopId . ':' . $this->secretKey);
        $idempotenceKey = Str::uuid()->toString();

        $headers = [
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/json',
            'Idempotence-Key' => $idempotenceKey,
        ];

        Log::channel('single')->info('YooKassa request', [
            'method' => $method,
            'path' => $path,
            'url' => $url,
            'idempotence_key' => $idempotenceKey,
            'body' => $body,
        ]);

        if ($method === 'GET') {
            $response = Http::withHeaders($headers)->get($url);
        } else {
            $response = Http::withHeaders($headers)->post($url, $body);
        }

        $httpStatus = $response->status();
        $data = $response->json();

        Log::channel('single')->info('YooKassa response', [
            'method' => $method,
            'path' => $path,
            'http_status' => $httpStatus,
            'response_body' => is_array($data) ? $data : $response->body(),
        ]);

        if (! is_array($data)) {
            return ['error' => 'Invalid response from YooKassa', 'http_status' => $httpStatus];
        }

        return $data;
    }
}
