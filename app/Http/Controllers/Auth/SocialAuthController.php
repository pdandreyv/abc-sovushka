<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Перенаправление на провайдера для авторизации
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Обработка callback от провайдера
     */
    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

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

            return redirect()->intended('/dashboard')->with('success', 'Вы успешно вошли через ' . $this->getProviderName($provider) . '!');
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Ошибка авторизации через ' . $this->getProviderName($provider) . '. Попробуйте еще раз.',
            ]);
        }
    }

    /**
     * Обработка VK ID / OK / Mail.Ru через фронтовый SDK
     */
    public function vkidCallback(Request $request)
    {
        $payload = $request->validate([
            'access_token' => ['required', 'string'],
            'provider' => ['nullable', 'string'],
        ]);

        $provider = $this->mapVkidProvider($payload['provider'] ?? null);

        $userInfoResponse = $this->fetchVkidUserInfo($payload['access_token']);

        if (!$userInfoResponse) {
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

        return response()->json([
            'redirect' => url('/dashboard'),
        ]);
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
        ];

        return $names[$provider] ?? $provider;
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
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$accessToken}\r\n",
                'timeout' => 10,
            ],
        ]);

        $responseBody = @file_get_contents('https://id.vk.com/oauth2/userinfo', false, $context);

        if ($responseBody === false) {
            return null;
        }

        $decoded = json_decode($responseBody, true);

        return is_array($decoded) ? $decoded : null;
    }
}
