<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Вход через Telegram</title>
  <link rel="stylesheet" href="{{ asset_versioned('css/auth.css') }}">
  <style>
    .telegram-auth {
      text-align: center;
      padding: 24px;
    }
    .telegram-auth .back-link {
      display: inline-block;
      margin-top: 16px;
      color: #000;
      text-decoration: none;
    }
    .telegram-auth .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-box telegram-auth">
      <h2>Вход через Telegram</h2>
      <p>Нажмите кнопку ниже и подтвердите вход в Telegram.</p>

      @if(config('services.telegram.bot_name') && config('services.telegram.bot_token'))
        <script
          async
          src="https://telegram.org/js/telegram-widget.js?22"
          data-telegram-login="{{ config('services.telegram.bot_name') }}"
          data-size="large"
          data-userpic="false"
          data-auth-url="{{ route('social.telegram.callback') }}"
          data-request-access="write"
        ></script>
      @else
        <p>Не настроены параметры Telegram. Проверьте `.env`.</p>
      @endif

      <a href="{{ url('/') }}" class="back-link">Вернуться к входу</a>
    </div>
  </div>
</body>
</html>
