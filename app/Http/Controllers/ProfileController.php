<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Показать страницу профиля
     */
    public function show()
    {
        return view('profile.index');
    }

    /**
     * Обновить данные профиля
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'about' => 'nullable|string',
        ], [
            'last_name.required' => 'Поле Фамилия обязательно для заполнения.',
            'first_name.required' => 'Поле Имя обязательно для заполнения.',
            'email.required' => 'Поле Email обязательно для заполнения.',
            'email.email' => 'Email должен быть действительным адресом электронной почты.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
        ]);

        $user->update($request->only([
            'last_name',
            'first_name',
            'middle_name',
            'email',
            'phone',
            'role',
            'city',
            'organization',
            'about',
        ]));

        return back()->with('success', 'Данные профиля успешно обновлены!');
    }

    /**
     * Изменить пароль
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Введите текущий пароль.',
            'new_password.required' => 'Введите новый пароль.',
            'new_password.confirmed' => 'Пароли не совпадают.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Неверный текущий пароль.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Пароль успешно изменён!');
    }
}
