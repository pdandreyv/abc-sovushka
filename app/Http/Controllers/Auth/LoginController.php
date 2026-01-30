<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Показать форму входа
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Обработать запрос на вход
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Поле Email обязательно для заполнения.',
            'email.email' => 'Email должен быть действительным адресом электронной почты.',
            'password.required' => 'Поле Пароль обязательно для заполнения.',
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();
            
            return redirect()->intended('/dashboard')->with('success', 'Вы успешно вошли в систему!');
        }

        return back()->withErrors([
            'email' => 'Неверный email или пароль.',
        ])->withInput($request->only('email'));
    }
}
