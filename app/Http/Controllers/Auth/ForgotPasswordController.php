<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LetterTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    private const CACHE_PREFIX = 'password_reset:';
    private const TOKEN_TTL_MINUTES = 60;

    /**
     * Отправить ссылку на сброс пароля. Если пользователь есть — письмо отправлено, иначе — пользователь не найден.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Укажите email.',
            'email.email'    => 'Укажите корректный email.',
        ]);

        $email = $request->input('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()->with('forgot_error', 'Пользователь с таким email не найден.');
        }

        $token = Str::random(64);
        Cache::put(self::CACHE_PREFIX . $email, $token, now()->addMinutes(self::TOKEN_TTL_MINUTES));

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);

        $sent = app(LetterTemplateService::class)->send('password_reset', $email, [
            'user_name'      => trim($user->first_name . ' ' . $user->last_name) ?: null,
            'reset_url'      => $resetUrl,
            'expire_minutes' => (string) self::TOKEN_TTL_MINUTES,
            'year'           => now()->year,
        ]);

        if (! $sent) {
            return back()->with('forgot_error', 'Не удалось отправить письмо. Попробуйте позже.');
        }

        return back()->with('forgot_success', 'На указанный email отправлено письмо со ссылкой для восстановления пароля.');
    }

    /**
     * Страница сброса пароля (по ссылке из письма). Без входа — только форма «новый пароль».
     */
    public function showResetForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (! $token || ! $email) {
            return redirect()->route('login')->withErrors(['reset' => 'Неверная ссылка. Запросите восстановление пароля снова.']);
        }

        $cachedToken = Cache::get(self::CACHE_PREFIX . $email);
        if (! $cachedToken || ! hash_equals($cachedToken, $token)) {
            return redirect()->route('login')->withErrors(['reset' => 'Ссылка недействительна или истекла. Запросите восстановление пароля снова.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Сохранить новый пароль, войти и перенаправить в ЛК.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required'  => 'Введите новый пароль.',
            'password.min'       => 'Пароль должен содержать минимум 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        $email = $request->input('email');
        $token = $request->input('token');

        $cachedToken = Cache::get(self::CACHE_PREFIX . $email);
        if (! $cachedToken || ! hash_equals($cachedToken, $token)) {
            return redirect()->route('login')->withErrors(['reset' => 'Ссылка недействительна или истекла.']);
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            return redirect()->route('login')->withErrors(['reset' => 'Пользователь не найден.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);
        Cache::forget(self::CACHE_PREFIX . $email);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('profile.show')->with('success', 'Пароль успешно изменён. Рекомендуем проверить данные профиля.');
    }
}
