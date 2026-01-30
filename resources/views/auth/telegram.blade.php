<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Совушкина школа — Вход через Telegram</title>
  <link rel="stylesheet" href="{{ asset_versioned('css/auth.css') }}">
  <style>
    html, body {
      height: 100%;
    }
    body {
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f6f7fb;
    }
    .telegram-card {
      text-align: center;
      max-width: 420px;
      width: 100%;
      border: 1px solid #e6e6e6;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      border-radius: 16px;
      background: #fff;
      padding: 24px;
    }
  </style>
</head>
<body>
  <div class="telegram-card">
    <h2>Вход через Telegram</h2>
    <p>Нажмите кнопку ниже и подтвердите вход в Telegram.</p>

    @if(config('services.telegram.bot_name') && config('services.telegram.bot_token'))
      <div style="margin: 16px 0;">
        <script
          async
          src="https://telegram.org/js/telegram-widget.js?22"
          data-telegram-login="{{ config('services.telegram.bot_name') }}"
          data-size="large"
          data-userpic="false"
          data-auth-url="{{ route('social.telegram.callback') }}{{ $linkToken ? ('?link_token=' . $linkToken) : '' }}"
          data-request-access="write"
        ></script>
      </div>
    @else
      <div class="alert alert-danger" style="margin-top: 16px;">
        Не настроены параметры Telegram. Проверьте `.env`.
      </div>
    @endif

    <a href="{{ url('/') }}" class="link" style="margin-top: 16px; display: inline-block;">Вернуться к входу</a>
  </div>
</body>
</html>
