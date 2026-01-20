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

        try {
            $request->validate([
                'last_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
                'role' => 'nullable|string|max:50',
                'city' => 'nullable|string',
                'organization' => 'nullable|string',
                'about' => 'nullable|string',
            ], [
                'last_name.required' => 'Поле Фамилия обязательно для заполнения.',
                'first_name.required' => 'Поле Имя обязательно для заполнения.',
                'email.required' => 'Поле Email обязательно для заполнения.',
                'email.email' => 'Email должен быть действительным адресом электронной почты.',
                'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
                'phone.unique' => 'Пользователь с таким телефоном уже зарегистрирован.',
                'phone.max' => 'Телефон не должен превышать 20 символов.',
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
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->all())
                ->with('error', 'Пожалуйста, исправьте ошибки в форме.');
                
        } catch (\Illuminate\Database\QueryException $e) {
            // Обработка ошибок базы данных (например, дублирование уникальных полей)
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            if ($errorCode == 23000) { // Integrity constraint violation
                if (strpos($errorMessage, 'phone') !== false) {
                    return back()
                        ->withInput($request->all())
                        ->with('error', 'Пользователь с таким телефоном уже зарегистрирован. Пожалуйста, используйте другой номер.');
                } elseif (strpos($errorMessage, 'email') !== false) {
                    return back()
                        ->withInput($request->all())
                        ->with('error', 'Пользователь с таким email уже зарегистрирован. Пожалуйста, используйте другой email.');
                }
            }
            
            return back()
                ->withInput($request->all())
                ->with('error', 'Произошла ошибка при сохранении данных. Попробуйте еще раз.');
                
        } catch (\Exception $e) {
            return back()
                ->withInput($request->all())
                ->with('error', 'Произошла непредвиденная ошибка. Попробуйте еще раз или обратитесь в поддержку.');
        }
    }

    /**
     * Изменить пароль
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'new_password' => ['required', 'confirmed', Password::min(8)],
            ], [
                'current_password.required' => 'Введите текущий пароль.',
                'new_password.required' => 'Введите новый пароль.',
                'new_password.min' => 'Пароль должен содержать минимум 8 символов.',
                'new_password.confirmed' => 'Пароли не совпадают.',
            ]);

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Неверный текущий пароль.',
                        'error_type' => 'current_password'
                    ], 422);
                }
                return back()
                    ->withErrors(['current_password' => 'Неверный текущий пароль.'])
                    ->with('password_error', 'Неверный текущий пароль.');
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Вы успешно сменили пароль!'
                ]);
            }

            return back()->with('password_success', 'Вы успешно сменили пароль!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = '';
            
            // Получаем первое сообщение об ошибке для более понятного отображения
            foreach ($errors as $fieldErrors) {
                if (is_array($fieldErrors) && count($fieldErrors) > 0) {
                    $firstError = $fieldErrors[0];
                    break;
                }
            }
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $firstError ?: 'Пожалуйста, исправьте ошибки в форме.',
                    'errors' => $errors
                ], 422);
            }
            return back()
                ->withErrors($errors)
                ->with('password_error', $firstError ?: 'Пожалуйста, исправьте ошибки в форме.');
                
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Произошла ошибка при изменении пароля. Попробуйте еще раз.'
                ], 500);
            }
            return back()
                ->with('password_error', 'Произошла ошибка при изменении пароля. Попробуйте еще раз.');
        }
    }
}
