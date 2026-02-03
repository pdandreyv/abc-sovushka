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
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ViewerController;

// Главная страница - форма входа
Route::get('/', function (Request $request) {
    if (\Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('dashboard');
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
Route::get('/auth/telegram', [SocialAuthController::class, 'telegramRedirect'])->name('social.telegram.redirect');
Route::match(['get', 'post'], '/auth/telegram/callback', [SocialAuthController::class, 'telegramCallback'])->name('social.telegram.callback');
Route::get('/auth/vkid/link', [SocialAuthController::class, 'vkidLink'])->name('social.vkid.link');
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
    Route::post('/subscriptions/recurring/{level}', [SubscriptionController::class, 'toggleRecurring'])
        ->name('subscriptions.recurring.toggle');
    Route::get('/subscriptions/checkout', [SubscriptionPaymentController::class, 'show'])->name('subscriptions.checkout');
    Route::post('/subscriptions/checkout', [SubscriptionPaymentController::class, 'create'])->name('subscriptions.checkout.create');
    Route::post('/subscriptions/checkout/confirm', [SubscriptionPaymentController::class, 'confirm'])->name('subscriptions.checkout.confirm');

    // Предметы и темы (без проверки подписки)
    Route::get('/subjects/{level}', [SubjectController::class, 'index'])->name('subjects.index');
    Route::get('/subjects/{level}/{subject}', [SubjectController::class, 'show'])->name('subjects.show');
    Route::get('/subjects/{level}/{subject}/materials/{topic}', [SubjectController::class, 'materials'])->name('subjects.materials');

    // Просмотр файлов
    Route::get('/viewer', [ViewerController::class, 'show'])->name('viewer.show');
});

// Публичные страницы (должен быть в конце, чтобы не перехватывать другие маршруты)
Route::get('/{url}', [PageController::class, 'show'])->name('page.show');
