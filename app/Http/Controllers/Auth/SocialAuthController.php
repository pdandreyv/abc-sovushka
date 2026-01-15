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
        ];

        return $names[$provider] ?? $provider;
    }
}
