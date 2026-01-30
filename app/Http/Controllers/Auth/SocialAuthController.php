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
        if ($request->boolean('link') && Auth::check()) {
            $request->session()->put('social_link_user_id', Auth::id());
        }

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

        $linkUser = $this->resolveLinkingUser($request);
        if ($linkUser) {
            $this->storeUserSocial($linkUser, $provider, $socialUser->getId(), $socialUser->getEmail());
            $this->clearLinkingSession($request);

            return redirect()->route('profile.show')->with('success', 'Соцсеть успешно привязана.');
        }

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

        $this->storeUserSocial($user, $provider, $socialUser->getId(), $socialUser->getEmail());

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

        $linkUser = $this->resolveLinkingUser($request);

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

        if ($linkUser) {
            $this->storeUserSocial($linkUser, $provider, $socialId, $email);
            $this->clearLinkingSession($request);

            return response()->json([
                'redirect' => route('profile.show'),
            ]);
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

        $this->storeUserSocial($user, $provider, $socialId, $email);

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
        $request = request();
        $linkToken = null;
        if ($request->boolean('link') && Auth::check()) {
            $request->session()->put('social_link_user_id', Auth::id());
            $linkToken = $this->makeLinkToken(Auth::id());
        }

        Log::channel('social_auth')->info('Telegram auth page opened', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        return view('auth.telegram', [
            'linkToken' => $linkToken,
        ]);
    }

    /**
     * Страница привязки VK ID
     */
    public function vkidLink(Request $request)
    {
        if (Auth::check()) {
            $request->session()->put('social_link_user_id', Auth::id());
        }

        return view('auth.vkid-link', [
            'vkidAppId' => config('services.vkontakte.client_id'),
            'vkidRedirectUrl' => url('/auth/vkontakte/callback'),
        ]);
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
            'link_token_query' => (bool) $request->query('link_token'),
            'link_token_body' => (bool) $request->input('link_token'),
            'session_link_user_id' => $request->session()->get('social_link_user_id'),
            'auth_user_id' => Auth::id(),
        ]);

        $linkUser = $this->resolveLinkingUser($request);
        if (!$linkUser) {
            $linkUser = $this->resolveLinkingUserFromToken($request);
        }
        Log::channel('social_auth')->info('Telegram link resolution', [
            'resolved_user_id' => $linkUser?->id,
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

        if ($linkUser) {
            $this->storeUserSocial($linkUser, 'telegram', $telegramId, $payload['username'] ?? null);
            $this->clearLinkingSession($request);

            return redirect()->route('profile.show')->with('success', 'Соцсеть успешно привязана.');
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

        $this->storeUserSocial($user, 'telegram', $telegramId, $payload['username'] ?? null);

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
        unset($data['link_token']);

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
            $clientId = config('services.vkontakte.client_id');
            if (!$clientId) {
                Log::channel('social_auth')->error('VKID userinfo missing client_id');
                return null;
            }

            $endpoint = 'https://id.vk.com/oauth2/user_info';

            $response = $client->get($endpoint, [
                'http_errors' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'client_id' => $clientId,
                ],
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status >= 200 && $status < 300) {
                $decoded = json_decode($body, true);
                if (!is_array($decoded)) {
                    return null;
                }
                if (isset($decoded['error'])) {
                    Log::channel('social_auth')->warning('VKID userinfo error payload', [
                        'endpoint' => $endpoint,
                        'error' => $decoded['error'],
                        'error_description' => $decoded['error_description'] ?? null,
                    ]);
                    return null;
                }
                return $decoded;
            }

            Log::channel('social_auth')->warning('VKID userinfo non-200', [
                'endpoint' => $endpoint,
                'status' => $status,
                'body' => mb_substr($body, 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::channel('social_auth')->error('VKID userinfo request error', [
                'message' => $e->getMessage(),
                'token_length' => strlen($accessToken),
            ]);
        }

        return null;
    }

    private function storeUserSocial(User $user, string $provider, string $socialId, ?string $email = null): void
    {
        $provider = strtolower(trim($provider));

        $typeMap = [
            'vkontakte' => 1,
            'vk' => 1,
            'vkid' => 1,
            'yandex' => 4,
            'mail_ru' => 5,
            'mailru' => 5,
            'ok_ru' => 6,
            'telegram' => 7,
        ];

        $type = $typeMap[$provider] ?? 0;
        if ($type === 0) {
            return;
        }

        $now = now();
        $firstName = $user->first_name ?? '';
        $lastName = $user->last_name ?? '';

        \Illuminate\Support\Facades\DB::table('user_socials')->updateOrInsert(
            [
                'type' => $type,
                'uid' => (string) $socialId,
            ],
            [
                'user' => $user->id,
                'email' => $email ?? $user->email ?? '',
                'login' => $email ?? $user->email ?? '',
                'gender' => 0,
                'name' => $firstName,
                'surname' => $lastName,
                'birthday' => '1970-01-01',
                'avatar' => '',
                'link' => '',
                'last_visit' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function resolveLinkingUser(Request $request): ?User
    {
        $linkUserId = $request->session()->get('social_link_user_id');
        if (!$linkUserId) {
            return null;
        }

        if (!Auth::check() || Auth::id() !== (int) $linkUserId) {
            return null;
        }

        return Auth::user();
    }

    private function clearLinkingSession(Request $request): void
    {
        $request->session()->forget('social_link_user_id');
    }

    private function makeLinkToken(int $userId): string
    {
        $timestamp = time();
        $payload = $userId . '|' . $timestamp;
        $signature = hash_hmac('sha256', $payload, config('app.key'));
        return base64_encode($payload . '|' . $signature);
    }

    private function resolveLinkingUserFromToken(Request $request): ?User
    {
        $token = $request->query('link_token') ?: $request->input('link_token');
        if (!$token) {
            return null;
        }

        $decoded = base64_decode($token, true);
        if (!$decoded) {
            return null;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return null;
        }

        [$userId, $timestamp, $signature] = $parts;
        if (!ctype_digit($userId) || !ctype_digit($timestamp)) {
            return null;
        }

        $payload = $userId . '|' . $timestamp;
        $expected = hash_hmac('sha256', $payload, config('app.key'));
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        if ((time() - (int) $timestamp) > 600) {
            return null;
        }

        return User::find((int) $userId);
    }
}
