<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        $email = $credentials['email'];
        $user = User::query()->where('email', $email)->first();
        $hasPasswordCol = $user && $user->password !== '' && $user->password !== null;
        $passwordCheck = $user && $hasPasswordCol ? Hash::check($credentials['password'], $user->password) : false;
        Log::channel('single')->info('auth_lk_login', [
            'email' => $email,
            'user_found' => (bool) $user,
            'user_id' => $user?->id,
            'has_password_col' => $hasPasswordCol,
            'password_check' => $passwordCheck,
            'attempt_result' => $user ? 'will_attempt' : 'skip_no_user',
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();
            Log::channel('single')->info('auth_lk_login', ['email' => $email, 'result' => 'success']);
            return redirect()->intended('/dashboard')->with('success', 'Вы успешно вошли в систему!');
        }

        Log::channel('single')->info('auth_lk_login', ['email' => $email, 'result' => 'failed']);
        return back()->withErrors([
            'email' => 'Неверный email или пароль.',
        ])->withInput($request->only('email'));
    }
}
