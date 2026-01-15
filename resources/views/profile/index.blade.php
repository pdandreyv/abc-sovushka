@extends('layouts.app')

@section('title', 'Личные данные — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<!-- ===== ЛЕВАЯ ПАНЕЛЬ (sidebar): навигация личного кабинета ===== -->
<div class="sidebar">
  <div>
    <img alt="Логотип" class="logo" src="{{ asset('images/logo.png') }}"/>
    <div class="user-name">{{ strtoupper(Auth::user()->first_name . ' ' . Auth::user()->last_name) }}</div>
    <a href="#" class="user-logout-link" data-logout>Выйти</a>
    <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: none;">
      @csrf
    </form>
    <div class="menu">
      <button class="active" type="button" onclick="window.location.href='{{ route('profile.show') }}'">Личные данные</button>
      <button onclick="window.location.href='{{ route('dashboard') }}'" type="button">Портфолио</button>
      <button onclick="window.location.href='{{ route('dashboard') }}'" type="button">Подписки</button>
      <button onclick="window.location.href='{{ route('dashboard') }}'" type="button">Кладовая идей</button>
    </div>
  </div>
</div>
<div class="main">
  <!-- Основной контент страницы (шапка, хлебные крошки, карточки и т.д.) -->
  <div class="header">
    <div class="breadcrumbs">Главная / Кабинет / Личные данные</div>
    <div class="header-icons">
      <img alt="Подписка" src="{{ asset('images/subscription_icon.png') }}"/>
      <a class="subscription-status subscription-status-link" href="{{ route('dashboard') }}">Подписок нет: выбрать / оформить</a>
      <img alt="Поддержка" src="{{ asset('images/support_icon.png') }}"/>
    </div>
  </div>
  <div class="content">
    <h1>Проверьте и сохраните данные профиля</h1>
    <p class="page-hint">
      Мы создали для вас личный кабинет. Пожалуйста, проверьте данные, добавьте (при желании) дополнительную информацию и нажмите «Сохранить».
      После сохранения вы сможете редактировать профиль в любой момент.
    </p>
    <div class="cards">
      <!-- Профиль: проверка/добавление данных -->
      <div class="card" style="grid-column: 1 / -1;">
        <div class="card-header">
          <h3>Личные данные</h3>
          <div class="card-actions">
            <button class="btn btn-secondary" id="editProfileBtn" style="display:none" type="button">Редактировать</button>
            <button class="btn btn-primary" id="saveProfileBtn" type="button">Сохранить</button>
          </div>
        </div>
        @if (session('success'))
          <div class="toast toast-success show" id="profileSaved">✅ {{ session('success') }}</div>
        @else
          <div class="toast toast-success" hidden id="profileSaved">✅ Данные сохранены</div>
        @endif
        
        @if (session('error'))
          <div class="toast toast-error show" id="profileError">❌ {{ session('error') }}</div>
        @else
          <div class="toast toast-error" hidden id="profileError">❌ Ошибка</div>
        @endif
        
        @if ($errors->any())
          <div class="toast toast-error show" id="validationErrors">
            ❌ <strong>Ошибки валидации:</strong>
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
          <h3>Безопасность</h3>
        </div>
        @if (session('password_success'))
          <div class="toast toast-success show" id="passwordSaved" style="display: inline-flex !important; opacity: 1 !important;">✅ {{ session('password_success') }}</div>
        @else
          <div class="toast toast-success" hidden id="passwordSaved">✅ Пароль обновлён</div>
        @endif
        
        @if (session('password_error'))
          <div class="toast toast-error show" id="passwordError" style="display: inline-flex !important; opacity: 1 !important;">❌ {{ session('password_error') }}</div>
        @else
          <div class="toast toast-error" hidden id="passwordError">❌ Ошибка</div>
        @endif
        <div class="field">
          <div class="row-between">
            <label for="password_mask">Пароль</label>
            <a class="link" href="#" id="changePasswordLink">Сменить пароль</a>
          </div>
          <input disabled id="password_mask" type="password" value="********"/>
        </div>
        <div hidden id="changePasswordPanel">
          <form id="passwordForm" method="POST" action="{{ route('profile.change-password') }}">
            @csrf
            <div class="form-grid" style="margin-top: 12px;">
              <div class="field full">
                <label for="current_password">Текущий пароль</label>
                <input autocomplete="current-password" id="current_password" name="current_password" required type="password"/>
                @error('current_password')
                  <div class="error-text">{{ $message }}</div>
                @enderror
              </div>
              <div class="field">
                <label for="new_password">Новый пароль</label>
                <input autocomplete="new-password" id="new_password" name="new_password" required type="password"/>
              </div>
              <div class="field">
                <label for="repeat_password">Повторите новый пароль</label>
                <input autocomplete="new-password" id="repeat_password" name="new_password_confirmation" required type="password"/>
              </div>
              <div class="field full">
                <button class="btn btn-primary" type="submit">Сохранить пароль</button>
                @error('new_password')
                  <div class="error-text">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </form>
        </div>
      </div>
      <!-- Подписки (пустое состояние) -->
      <div class="card">
        <h3>Подписки</h3>
        <p>У вас пока нет активных подписок. Выберите класс или направление — материалы откроются сразу после оформления.</p>
        <button class="btn btn-primary" type="button" onclick="window.location.href='{{ route('dashboard') }}'">Оформить подписку</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
