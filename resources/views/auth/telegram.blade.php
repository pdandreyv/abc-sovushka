@extends('layouts.app')

@section('title', 'Совушкина школа — Вход через Telegram')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/auth.css') }}">
@endpush

@section('content')
<div class="container">
  <div class="left">
    <img src="{{ asset('images/logo.png') }}" alt="Логотип" />
    <div class="welcome-text">
      <h3>Вход через Telegram</h3>
      <p>Подключите Telegram и входите в личный кабинет в один клик.</p>
    </div>
  </div>

  <div class="right">
    <div class="form-box" style="text-align: center;">
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
            data-auth-url="{{ route('social.telegram.callback') }}"
            data-request-access="write"
          ></script>
        </div>
      @else
        <div class="alert alert-danger" style="margin-top: 16px;">
          Не настроены параметры Telegram. Проверьте `.env`.
        </div>
      @endif

      <div class="helper-text" style="margin-top: 12px;">
        Если видите ошибку <strong>Bot domain invalid</strong>, задайте домен в @BotFather:
        <br />
        <span style="font-weight: 600;">{{ request()->getHost() }}</span>
      </div>

      <a href="{{ url('/') }}" class="link" style="margin-top: 16px; display: inline-block;">Вернуться к входу</a>
    </div>
  </div>
</div>
@endsection
