<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>404 — Ой… этой страницы нет</title>
  <link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
</head>
<body>
  <div class="sidebar">
    <div>
      <img alt="Логотип" class="logo" src="{{ asset('images/logo.png') }}" />
      @if(\Illuminate\Support\Facades\Auth::check())
        <div class="user-name">{{ strtoupper(Auth::user()->first_name . ' ' . Auth::user()->last_name) }}</div>
        <div class="user-code">ID: {{ Auth::user()->user_code }}</div>
        <a href="#" class="user-logout-link" data-logout>{{ site_lang('lk_menu|logout', 'Выйти') }}</a>
        <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: none;">
          @csrf
        </form>
        <div class="menu">
          <button type="button" data-href="{{ route('profile.show') }}">{{ site_lang('lk_menu|profile', 'Личные данные') }}</button>
          <button type="button" data-href="{{ route('dashboard') }}">{{ site_lang('lk_menu|portfolio', 'Портфолио') }}</button>
          <button type="button" data-href="{{ route('subscriptions.index') }}">{{ site_lang('lk_menu|subscriptions', 'Подписки') }}</button>
          <button type="button" data-href="{{ route('ideas.index') }}">{{ site_lang('lk_menu|ideas', 'Кладовая идей') }}</button>
        </div>
      @else
        <div class="user-name">Совушкина школа</div>
        <a class="user-logout-link" href="{{ url('/') }}">На главную</a>
        <div class="menu">
          <a class="menu-link" href="{{ url('/') }}">Главная</a>
          <a class="menu-link" href="{{ url('/login') }}">Войти</a>
        </div>
      @endif
    </div>
  </div>

  <div class="main">
    <div class="header">
      <div class="breadcrumbs">Ошибка / 404</div>
      <div class="header-icons">
        <img alt="Поддержка" src="{{ asset('images/support_icon.png') }}" />
      </div>
    </div>

    <div class="content">
      <div class="card error-card">
        <div class="error-hero">
          <img
            src="{{ asset('images/404-page.png') }}"
            alt="Совушка ищет страницу"
            style="max-width: 260px; width: 100%; margin-right: 20px;"
          />

          <div>
            <h1 class="error-title">Ой… этой страницы нет</h1>
            <p class="error-text">
              Совушка уже ищет: заглянула в папки, проверила полки — а тут пусто.<br />
              Проверьте ссылку, вернитесь на главную или напишите в службу заботы.
            </p>

            <div class="card-actions" style="margin-top: 16px;">
              <a class="btn btn-primary" href="{{ url('/') }}">Главная</a>
              <a
                class="btn btn-secondary"
                href="{{ site_lang('common|support_vk_url', 'https://vk.com/im/convo/-93773680?entrypoint=community_page&tab=all') }}"
                target="_blank"
                rel="noopener"
              >Служба заботы</a>
            </div>
          </div>
        </div>

        @if(\Illuminate\Support\Facades\Auth::check())
        <div class="error-links" style="margin-top: 16px;">
          <a href="{{ url('/subscriptions') }}">Подписки</a>
          <span class="dot">•</span>
          <a href="{{ url('/ideas') }}">Кладовая идей</a>
          <span class="dot">•</span>
          <a href="{{ url('/dashboard') }}">Портфолио</a>
        </div>
        @endif

      </div>
    </div>
  </div>
</body>
<script>
  document.querySelectorAll('[data-href]').forEach(function(button) {
    button.addEventListener('click', function() {
      window.location.href = this.dataset.href;
    });
  });
</script>
</html>
