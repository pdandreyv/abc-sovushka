@extends('layouts.app')

@section('title', site_lang('lk_profile|page_title', 'Личные данные — Совушкина школа'))

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => 'profile'])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => site_lang('lk_profile|crumb_home', 'Главная'), 'url' => url('/')],
      ['label' => site_lang('lk_profile|crumb_cabinet', 'Кабинет'), 'url' => route('dashboard')],
      ['label' => site_lang('lk_profile|crumb_profile', 'Личные данные'), 'url' => null],
    ],
  ])
  <div class="content">
    <h1>{{ site_lang('lk_profile|heading', 'Проверьте и сохраните данные профиля') }}</h1>
    <p class="page-hint">
      {{ site_lang('lk_profile|hint', 'Мы создали для вас личный кабинет. Пожалуйста, проверьте данные, добавьте (при желании) дополнительную информацию и нажмите «Сохранить». После сохранения вы сможете редактировать профиль в любой момент.') }}
    </p>
    <div class="cards">
      <!-- Профиль: проверка/добавление данных -->
      <div class="card" style="grid-column: 1 / -1;">
        <div class="card-header">
          <h3>{{ site_lang('lk_profile|card_title', 'Личные данные') }} <span class="user-code-inline">ID: {{ Auth::user()->user_code }}</span></h3>
          <div class="card-actions">
            <button class="btn btn-secondary" id="editProfileBtn" style="display:none" type="button">{{ site_lang('lk_profile|edit', 'Редактировать') }}</button>
            <button class="btn btn-primary" id="saveProfileBtn" type="button">{{ site_lang('lk_profile|save', 'Сохранить') }}</button>
          </div>
        </div>
        @if (session('success'))
          <div class="toast toast-success show" id="profileSaved">✅ {{ session('success') }}</div>
        @else
          <div class="toast toast-success" hidden id="profileSaved">✅ {{ site_lang('lk_profile|saved', 'Данные сохранены') }}</div>
        @endif
        
        @if (session('error'))
          <div class="toast toast-error show" id="profileError">❌ {{ session('error') }}</div>
        @else
          <div class="toast toast-error" hidden id="profileError">❌ {{ site_lang('lk_profile|error', 'Ошибка') }}</div>
        @endif
        
        @if ($errors->any())
          <div class="toast toast-error show" id="validationErrors">
            ❌ <strong>{{ site_lang('lk_profile|validation_title', 'Ошибки валидации:') }}</strong>
            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <form id="profileForm" method="POST" action="{{ route('profile.update') }}">
          @csrf
          @method('PUT')
          <div class="form-grid">
            <div class="field">
              <label for="last_name">Фамилия</label>
              <input id="last_name" name="last_name" placeholder="Фамилия" required type="text" value="{{ old('last_name', Auth::user()->last_name) }}" class="@error('last_name') input-error @enderror"/>
              @error('last_name')
                <div class="error-text">{{ $message }}</div>
              @enderror
            </div>
            <div class="field">
              <label for="first_name">Имя</label>
              <input id="first_name" name="first_name" placeholder="Имя" required type="text" value="{{ old('first_name', Auth::user()->first_name) }}" class="@error('first_name') input-error @enderror"/>
              @error('first_name')
                <div class="error-text">{{ $message }}</div>
              @enderror
            </div>
            <div class="field">
              <label for="middle_name">Отчество</label>
              <input id="middle_name" name="middle_name" placeholder="Отчество" type="text" value="{{ old('middle_name', Auth::user()->middle_name) }}" class="@error('middle_name') input-error @enderror"/>
              @error('middle_name')
                <div class="error-text">{{ $message }}</div>
              @enderror
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" name="email" placeholder="Email" required type="email" value="{{ old('email', Auth::user()->email) }}" class="@error('email') input-error @enderror"/>
              @error('email')
                <div class="error-text">{{ $message }}</div>
              @enderror
            </div>
            <div class="field">
              <label for="role">Вы</label>
              <select id="role" name="role">
                <option value="teacher" {{ old('role', Auth::user()->role) == 'teacher' ? 'selected' : '' }}>Учитель</option>
                <option value="educator" {{ old('role', Auth::user()->role) == 'educator' ? 'selected' : '' }}>Воспитатель</option>
                <option value="tutor" {{ old('role', Auth::user()->role) == 'tutor' ? 'selected' : '' }}>Педагог доп. образования</option>
                <option value="parent" {{ old('role', Auth::user()->role) == 'parent' ? 'selected' : '' }}>Родитель</option>
                <option value="other" {{ old('role', Auth::user()->role) == 'other' ? 'selected' : '' }}>Другое</option>
              </select>
            </div>
            <div class="field">
              <label for="phone">Телефон (необязательно)</label>
              <input id="phone" name="phone" placeholder="+7 (___) ___-__-__" type="tel" value="{{ old('phone', Auth::user()->phone) }}" class="@error('phone') input-error @enderror"/>
              @error('phone')
                <div class="error-text">{{ $message }}</div>
              @enderror
            </div>
            <div class="field">
              <label for="city">Город (необязательно)</label>
              <input id="city" name="city" placeholder="Город" type="text" value="{{ old('city', Auth::user()->city) }}"/>
            </div>
            <div class="field">
              <label for="organization">Школа/сад (необязательно)</label>
              <input id="organization" name="organization" placeholder="Название организации" type="text" value="{{ old('organization', Auth::user()->organization) }}"/>
            </div>
            <div class="field full">
              <label for="about">Дополнительная информация (необязательно)</label>
              <textarea id="about" name="about" placeholder="Например: класс, стаж, интересующие предметы, пожелания по материалам">{{ old('about', Auth::user()->about) }}</textarea>
              <div class="helper-text">Эти данные помогут точнее рекомендовать вам материалы и быстрее отвечать в поддержке.</div>
            </div>
          </div>
        </form>
      </div>
      <!-- Безопасность -->
      <div class="card">
        <div class="card-header">
          <h3>{{ site_lang('lk_profile|security_title', 'Безопасность') }}</h3>
        </div>
        @if (session('password_success'))
          <div class="toast toast-success show" id="passwordSaved" style="display: inline-flex !important; opacity: 1 !important;">✅ {{ session('password_success') }}</div>
        @else
          <div class="toast toast-success" hidden id="passwordSaved">✅ Пароль обновлён</div>
        @endif
        
        <div class="toast toast-error" hidden id="passwordError">
          <div id="passwordErrorContent"></div>
        </div>
        <div class="field">
          <div class="row-between">
            <label for="password_mask">{{ site_lang('lk_profile|password', 'Пароль') }}</label>
            <a class="link" href="#" id="changePasswordLink">{{ site_lang('lk_profile|change_password', 'Сменить пароль') }}</a>
          </div>
          <input disabled id="password_mask" type="password" value="********"/>
        </div>
        <div hidden id="changePasswordPanel">
          <form id="passwordForm" method="POST" action="{{ route('profile.change-password') }}" data-ajax-form="true">
            @csrf
            <div class="form-grid" style="margin-top: 12px;">
              <div class="field full">
                <label for="current_password">{{ site_lang('lk_profile|current_password', 'Текущий пароль') }}</label>
                <input autocomplete="current-password" id="current_password" name="current_password" required type="password"/>
              </div>
              <div class="field">
                <label for="new_password">{{ site_lang('lk_profile|new_password', 'Новый пароль') }}</label>
                <input autocomplete="new-password" id="new_password" name="new_password" required type="password"/>
              </div>
              <div class="field">
                <label for="repeat_password">{{ site_lang('lk_profile|repeat_password', 'Повторите новый пароль') }}</label>
                <input autocomplete="new-password" id="repeat_password" name="new_password_confirmation" required type="password"/>
              </div>
              <div class="field full">
                <button class="btn btn-primary" type="submit">{{ site_lang('lk_profile|save_password', 'Сохранить пароль') }}</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <!-- Подписки (пустое состояние) -->
      <div class="card">
        <h3>{{ site_lang('lk_profile|subscriptions_title', 'Подписки') }}</h3>
        <p>{{ site_lang('lk_profile|subscriptions_empty', 'У вас пока нет активных подписок. Выберите класс или направление — материалы откроются сразу после оформления.') }}</p><br />
        <button class="btn btn-primary" type="button" data-href="{{ route('subscriptions.index') }}">{{ site_lang('lk_profile|subscriptions_cta', 'Оформить подписку') }}</button>
      </div>
      <!-- Привязанные соцсети -->
      <div class="card">
        <h3>Привязанные соцсети</h3>
        <div class="social-bindings">
          <div class="social-row">
            <div class="social-name">Яндекс</div>
            @if(in_array('yandex', $linkedProviders ?? [], true))
              <span class="social-status is-linked">Привязана</span>
            @else
              <a class="btn btn-secondary btn-sm" href="{{ route('social.redirect', ['provider' => 'yandex', 'link' => 1]) }}">Привязать</a>
            @endif
          </div>
          <div class="social-row">
            <div class="social-name">VK ID</div>
            @if(in_array('vkontakte', $linkedProviders ?? [], true) || in_array('ok_ru', $linkedProviders ?? [], true) || in_array('mail_ru', $linkedProviders ?? [], true))
              <span class="social-status is-linked">Привязана</span>
            @else
              <a class="btn btn-secondary btn-sm" href="{{ route('social.vkid.link') }}">Привязать</a>
            @endif
          </div>
          <div class="social-row">
            <div class="social-name">Telegram</div>
            @if(in_array('telegram', $linkedProviders ?? [], true))
              <span class="social-status is-linked">Привязана</span>
            @else
              <a class="btn btn-secondary btn-sm" href="{{ route('social.telegram.redirect', ['link' => 1]) }}">Привязать</a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
<script>
  document.querySelectorAll('[data-href]').forEach(function(button) {
    button.addEventListener('click', function() {
      window.location.href = this.dataset.href;
    });
  });
</script>
@endpush
