<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LetterTemplateService;
use App\Services\UserActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Показать форму регистрации
     */
    public function showRegistrationForm()
    {
        return view('auth.login');
    }

    /**
     * Обработать запрос на регистрацию
     */
    public function register(Request $request)
    {
        $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'last_name.required' => 'Поле Фамилия обязательно для заполнения.',
            'first_name.required' => 'Поле Имя обязательно для заполнения.',
            'email.required' => 'Поле Email обязательно для заполнения.',
            'email.email' => 'Email должен быть действительным адресом электронной почты.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.required' => 'Поле Пароль обязательно для заполнения.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        $user = User::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'remind' => DB::raw('CURRENT_TIMESTAMP')
        ]);

        $confirmCode = Str::upper(Str::random(6));
        $confirmUntil = now()->addHours(24);
        Cache::put('registration_confirm:' . $user->email, [
            'code' => $confirmCode,
            'user_id' => $user->id,
        ], $confirmUntil);

        $letterSent = app(LetterTemplateService::class)->send('registration_confirm', $user->email, [
            'subject' => 'Подтвердите регистрацию в «Совушкина школа»',
            'year' => now()->year,
            'user_name' => trim($user->first_name . ' ' . $user->last_name) ?: null,
            'confirm_code' => $confirmCode,
            'confirm_until' => $confirmUntil->format('d.m.Y H:i'),
            'confirm_url' => route('register.confirm', [
                'email' => $user->email,
                'code' => $confirmCode,
            ]),
        ]);
        if (! $letterSent) {
            Log::warning('Регистрация: письмо подтверждения не отправлено', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        Auth::login($user, true);
        UserActivityLogService::logLogin((int) $user->id, $request->ip() ?? '');

        return redirect('/profile')->with('success', 'Регистрация прошла успешно! Добро пожаловать! Пожалуйста, проверьте и сохраните данные профиля.');
    }

    /**
     * Подтверждение email по ссылке из письма.
     */
    public function confirmEmail(Request $request)
    {
        $email = $request->query('email');
        $code = $request->query('code');
        if (! $email || ! $code) {
            return redirect()->route('login')->withErrors(['confirm' => 'Неверная ссылка подтверждения.']);
        }
        $key = 'registration_confirm:' . $email;
        $data = Cache::get($key);
        if (! $data || ! isset($data['code']) || ! hash_equals($data['code'], $code)) {
            return redirect()->route('login')->withErrors(['confirm' => 'Код подтверждения неверный или истёк.']);
        }
        User::where('id', $data['user_id'])->update(['email_verified_at' => now()]);
        Cache::forget($key);
        return redirect()->route('login')->with('success', 'Email успешно подтверждён. Можете войти.');
    }
}
