<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Перенаправление на провайдера для авторизации
     */
    public function redirect(Request $request, $provider)
    {
        Log::channel('social_auth')->info('Social auth redirect', [
            'provider' => $provider,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Обработка callback от провайдера
     */
    public function callback($provider)
    {
        $request = request();
        Log::channel('social_auth')->info('Social auth callback start', [
            'provider' => $provider,
            'ip' => $request->ip(),
            'has_code' => $request->has('code'),
            'has_error' => $request->has('error'),
            'error' => $request->get('error'),
        ]);
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            Log::channel('social_auth')->warning('Social auth invalid state', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            try {
                $socialUser = Socialite::driver($provider)->stateless()->user();
            } catch (\Exception $inner) {
                Log::channel('social_auth')->error('Social auth failed after stateless', [
                    'provider' => $provider,
                    'error' => $inner->getMessage(),
                ]);
                return redirect()->route('login')->withErrors([
                    'email' => 'Ошибка авторизации через ' . $this->getProviderName($provider) . '. Попробуйте еще раз.',
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('social_auth')->error('Social auth failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('login')->withErrors([
                'email' => 'Ошибка авторизации через ' . $this->getProviderName($provider) . '. Попробуйте еще раз.',
            ]);
        }
        // Ищем пользователя по social_id и social_provider
        $user = User::where('social_id', $socialUser->getId())
            ->where('social_provider', $provider)
            ->first();

        if (!$user) {
            // Проверяем, есть ли пользователь с таким email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Если пользователь существует, привязываем социальную сеть
                $user->social_id = $socialUser->getId();
                $user->social_provider = $provider;
                $user->save();
            } else {
                // Создаем нового пользователя
                $nameParts = $this->parseName($socialUser->getName());

                $user = User::create([
                    'first_name' => $nameParts['first_name'] ?? '',
                    'last_name' => $nameParts['last_name'] ?? '',
                    'email' => $socialUser->getEmail() ?? $socialUser->getId() . '@' . $provider . '.local',
                    'social_id' => $socialUser->getId(),
                    'social_provider' => $provider,
                    'password' => bcrypt(str()->random(32)), // Генерируем случайный пароль
                ]);
            }
        }

        // Авторизуем пользователя
        Auth::login($user, true);

        Log::channel('social_auth')->info('Social auth success', [
            'provider' => $provider,
            'user_id' => $user->id,
            'social_id' => $socialUser->getId(),
            'email' => $user->email,
        ]);

        return redirect()->intended('/dashboard')->with('success', 'Вы успешно вошли через ' . $this->getProviderName($provider) . '!');
    }

    /**
     * Обработка VK ID / OK / Mail.Ru через фронтовый SDK
     */
    public function vkidCallback(Request $request)
    {
        Log::channel('social_auth')->info('VKID callback start', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'provider_payload' => $request->input('provider'),
            'token_length' => strlen((string) $request->input('access_token')),
            'client_id' => config('services.vkontakte.client_id'),
        ]);
        $payload = $request->validate([
            'access_token' => ['required', 'string'],
            'provider' => ['nullable', 'string'],
        ]);

        $provider = $this->mapVkidProvider($payload['provider'] ?? null);

        $userInfoResponse = $this->fetchVkidUserInfo($payload['access_token']);

        if (!$userInfoResponse) {
            Log::channel('social_auth')->error('VKID userinfo failed', [
                'provider' => $provider,
                'payload_provider' => $payload['provider'] ?? null,
            ]);
            return response()->json([
                'message' => 'Не удалось получить данные пользователя VK ID.',
            ], 422);
        }

        $userInfo = $userInfoResponse;
        $userData = $userInfo['user']
            ?? ($userInfo['data']['user'] ?? null)
            ?? ($userInfo['user_info'] ?? null);

        $socialId = data_get($userData, 'user_id')
            ?? data_get($userData, 'id')
            ?? data_get($userData, 'sub')
            ?? data_get($userInfo, 'user_id');

        if (!$socialId) {
            Log::channel('social_auth')->error('VKID userinfo missing social id', [
                'provider' => $provider,
                'user_info' => $userInfo,
            ]);
            return response()->json([
                'message' => 'Не удалось определить пользователя VK ID.',
            ], 422);
        }

        $email = data_get($userData, 'email')
            ?? data_get($userInfo, 'email')
            ?? data_get($userData, 'default_email');

        $firstName = data_get($userData, 'first_name')
            ?? data_get($userData, 'given_name');
        $lastName = data_get($userData, 'last_name')
            ?? data_get($userData, 'family_name');

        if (!$firstName && !$lastName) {
            $nameParts = $this->parseName(data_get($userData, 'name', ''));
            $firstName = $nameParts['first_name'] ?? '';
            $lastName = $nameParts['last_name'] ?? '';
        }

        $user = User::where('social_id', $socialId)
            ->where('social_provider', $provider)
            ->first();

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            $user->social_id = $socialId;
            $user->social_provider = $provider;
            $user->save();
        } else {
            $user = User::create([
                'first_name' => $firstName ?? '',
                'last_name' => $lastName ?? '',
                'email' => $email ?? ($socialId . '@' . $provider . '.local'),
                'social_id' => $socialId,
                'social_provider' => $provider,
                'password' => bcrypt(str()->random(32)),
            ]);
        }

        Auth::login($user, true);

        Log::channel('social_auth')->info('VKID auth success', [
            'provider' => $provider,
            'user_id' => $user->id,
            'social_id' => $socialId,
            'email' => $user->email,
        ]);

        return response()->json([
            'redirect' => url('/dashboard'),
        ]);
    }

    /**
     * Страница входа через Telegram
     */
    public function telegramRedirect()
    {
        Log::channel('social_auth')->info('Telegram auth page opened', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        return view('auth.telegram');
    }

    /**
     * Обработка callback от Telegram
     */
    public function telegramCallback(Request $request)
    {
        $payload = $request->all();
        Log::channel('social_auth')->info('Telegram callback start', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload_keys' => array_keys($payload),
            'telegram_id' => $payload['id'] ?? null,
            'auth_date' => $payload['auth_date'] ?? null,
        ]);

        if (!$this->isTelegramAuthValid($payload)) {
            Log::channel('social_auth')->warning('Telegram auth validation failed', [
                'telegram_id' => $payload['id'] ?? null,
            ]);
            return redirect()->route('login')->withErrors([
                'email' => 'Ошибка авторизации через Telegram. Попробуйте еще раз.',
            ]);
        }

        $telegramId = $payload['id'] ?? null;
        if (!$telegramId) {
            return redirect()->route('login')->withErrors([
                'email' => 'Ошибка авторизации через Telegram. Не удалось определить пользователя.',
            ]);
        }

        $firstName = $payload['first_name'] ?? '';
        $lastName = $payload['last_name'] ?? '';
        $email = $telegramId . '@telegram.local';

        $user = User::where('social_id', $telegramId)
            ->where('social_provider', 'telegram')
            ->first();

        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            $user->social_id = $telegramId;
            $user->social_provider = 'telegram';
            $user->save();
        } else {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'social_id' => $telegramId,
                'social_provider' => 'telegram',
                'password' => bcrypt(str()->random(32)),
            ]);
        }

        Auth::login($user, true);

        Log::channel('social_auth')->info('Telegram auth success', [
            'user_id' => $user->id,
            'telegram_id' => $telegramId,
            'email' => $user->email,
        ]);

        return redirect()->intended('/dashboard')->with('success', 'Вы успешно вошли через Telegram!');
    }

    /**
     * Парсинг имени пользователя
     */
    private function parseName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        
        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }

    /**
     * Получить название провайдера на русском
     */
    private function getProviderName($provider)
    {
        $names = [
            'vkontakte' => 'ВКонтакте',
            'yandex' => 'Яндекс',
            'odnoklassniki' => 'Одноклассники',
            'mail_ru' => 'Mail.ru',
            'telegram' => 'Telegram',
        ];

        return $names[$provider] ?? $provider;
    }

    /**
     * Проверка подписи Telegram Login Widget
     */
    private function isTelegramAuthValid(array $data): bool
    {
        $botToken = config('services.telegram.bot_token');
        if (!$botToken || !isset($data['hash'])) {
            return false;
        }

        $hash = $data['hash'];
        unset($data['hash']);

        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        sort($pairs);
        $checkString = implode("\n", $pairs);

        $secret = hash('sha256', $botToken, true);
        $calculated = hash_hmac('sha256', $checkString, $secret);

        if (!hash_equals($calculated, $hash)) {
            return false;
        }

        $authDate = isset($data['auth_date']) ? (int) $data['auth_date'] : 0;
        if ($authDate > 0 && (time() - $authDate) > 86400) {
            return false;
        }

        return true;
    }

    /**
     * Приведение провайдера из VKID SDK к нашим именам
     */
    private function mapVkidProvider(?string $provider): string
    {
        $provider = strtolower(trim($provider ?? ''));

        if ($provider === 'ok_ru') {
            return 'odnoklassniki';
        }

        if (in_array($provider, ['vk', 'vkid', 'vkontakte'], true)) {
            return 'vkontakte';
        }

        if ($provider === 'mail_ru') {
            return 'mail_ru';
        }

        return 'vkontakte';
    }

    /**
     * Получить userinfo от VK ID через простой HTTP-запрос
     */
    private function fetchVkidUserInfo(string $accessToken): ?array
    {
        $client = new Client([
            'timeout' => 10,
        ]);

        try {
            $endpoints = [
                'https://id.vk.com/oauth2/user_info',
                'https://id.vk.com/oauth2/userinfo',
            ];

            foreach ($endpoints as $endpoint) {
                $attempts = [
                    [
                        'method' => 'GET',
                        'options' => [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $accessToken,
                                'Accept' => 'application/json',
                            ],
                        ],
                    ],
                    [
                        'method' => 'POST',
                        'options' => [
                            'form_params' => [
                                'access_token' => $accessToken,
                            ],
                        ],
                    ],
                    [
                        'method' => 'GET',
                        'options' => [
                            'query' => [
                                'access_token' => $accessToken,
                            ],
                        ],
                    ],
                ];

                foreach ($attempts as $attempt) {
                    $response = $client->request($attempt['method'], $endpoint, array_merge([
                        'http_errors' => false,
                    ], $attempt['options']));

                    $status = $response->getStatusCode();
                    $body = (string) $response->getBody();

                    if ($status >= 200 && $status < 300) {
                        $decoded = json_decode($body, true);
                        if (!is_array($decoded)) {
                            continue;
                        }
                        if (isset($decoded['error'])) {
                            Log::channel('social_auth')->warning('VKID userinfo error payload', [
                                'endpoint' => $endpoint,
                                'method' => $attempt['method'],
                                'error' => $decoded['error'],
                                'error_description' => $decoded['error_description'] ?? null,
                            ]);
                            continue;
                        }
                        return $decoded;
                    }

                    Log::channel('social_auth')->warning('VKID userinfo non-200', [
                        'endpoint' => $endpoint,
                        'method' => $attempt['method'],
                        'status' => $status,
                        'body' => mb_substr($body, 0, 500),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::channel('social_auth')->error('VKID userinfo request error', [
                'message' => $e->getMessage(),
                'token_length' => strlen($accessToken),
            ]);
        }

        return null;
    }
}
