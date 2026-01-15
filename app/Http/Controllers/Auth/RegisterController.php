<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /**
     * Показать форму регистрации
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
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
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'last_name.required' => 'Поле Фамилия обязательно для заполнения.',
            'first_name.required' => 'Поле Имя обязательно для заполнения.',
            'email.required' => 'Поле Email обязательно для заполнения.',
            'email.email' => 'Email должен быть действительным адресом электронной почты.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.required' => 'Поле Пароль обязательно для заполнения.',
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

        Auth::login($user);

        return redirect('/profile')->with('success', 'Регистрация прошла успешно! Добро пожаловать! Пожалуйста, проверьте и сохраните данные профиля.');
    }
}
