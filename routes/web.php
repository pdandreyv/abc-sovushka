<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\SubscriptionController;

// Главная страница - форма входа или ловим callback соцсетей
Route::get('/', function (Request $request) {
    if ($request->has('code') || $request->has('error')) {
        $provider = $request->get('provider', 'yandex');
        if (!in_array($provider, ['vkontakte', 'yandex', 'odnoklassniki'], true)) {
            $provider = 'yandex';
        }

        return app(SocialAuthController::class)->callback($provider);
    }

    return app(LoginController::class)->showLoginForm();
});

// Маршруты аутентификации
Route::get('/login', function () {
    return redirect()->to('/');
})->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

// Социальная авторизация
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->where('provider', 'vkontakte|yandex|odnoklassniki')->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->where('provider', 'vkontakte|yandex|odnoklassniki')->name('social.callback');
Route::post('/auth/vkid/callback', [SocialAuthController::class, 'vkidCallback'])->name('social.vkid.callback');

// Защищенные маршруты
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Профиль пользователя
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    
    // Кладовая идей
    Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
    Route::post('/ideas/{idea}/like', [IdeaController::class, 'like'])->name('ideas.like');
    
    // Подписки
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
});

// Публичные страницы (должен быть в конце, чтобы не перехватывать другие маршруты)
Route::get('/{url}', [PageController::class, 'show'])->name('page.show');
