<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TestMailController extends Controller
{
    /**
     * Показать форму отправки тестового письма. Доступ только для администраторов.
     */
    public function index(): View|RedirectResponse
    {
        if (! $this->isAdmin()) {
            abort(403, 'Доступ только для администратора.');
        }

        return view('test-mail.index');
    }

    /**
     * Отправить тестовое письмо. Доступ только для администраторов.
     */
    public function send(Request $request): RedirectResponse
    {
        if (! $this->isAdmin()) {
            abort(403, 'Доступ только для администратора.');
        }

        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Укажите email получателя.',
            'email.email'    => 'Укажите корректный email.',
        ]);

        $email = $request->input('email');
        $subject = 'Тестовое письмо — ' . config('app.name');
        $body = $this->getTestBody();

        try {
            Mail::html($body, function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            return back()->with('success', "Тестовое письмо отправлено на {$email}.");
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Ошибка отправки: ' . $e->getMessage())->withInput();
        }
    }

    private function isAdmin(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['admin', 'administrator', 'superadmin', 'owner'], true);
    }

    private function getTestBody(): string
    {
        $time = now()->format('d.m.Y H:i:s');
        $driver = config('mail.default');
        $env = app()->environment();

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Тест</title></head>
<body style="font-family: sans-serif; padding: 20px;">
  <h2>Тестовое письмо</h2>
  <p>Это письмо отправлено с сервера для проверки настройки почты.</p>
  <p><strong>Время:</strong> {$time}</p>
  <p><strong>Окружение:</strong> {$env}</p>
  <p><strong>Mail driver:</strong> {$driver}</p>
  <hr>
  <p style="color:#666; font-size:12px;">Совушкина школа — тест отправки писем</p>
</body>
</html>
HTML;
    }
}
