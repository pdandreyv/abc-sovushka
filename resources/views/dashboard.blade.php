@extends('layouts.app')

@section('title', 'Личный кабинет — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
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
      <button onclick="window.location.href='{{ route('profile.show') }}'" type="button">Личные данные</button>
      <button onclick="window.location.href='{{ route('dashboard') }}'" type="button">Портфолио</button>
      <button onclick="window.location.href='{{ route('subscriptions.index') }}'" type="button">Подписки</button>
      <button onclick="window.location.href='{{ route('ideas.index') }}'" type="button">Кладовая идей</button>
    </div>
  </div>
</div>
<div class="main">
  <!-- Основной контент страницы (шапка, хлебные крошки, карточки и т.д.) -->
  <div class="header">
    <div class="breadcrumbs">Главная / Кабинет</div>
    <div class="header-icons">
      <img alt="Подписка" src="{{ asset('images/subscription_icon.png') }}"/>
      <a class="subscription-status subscription-status-link" href="{{ route('dashboard') }}">Осталось 5 дней подписки: продлить / отменить</a>
      <img alt="Поддержка" src="{{ asset('images/support_icon.png') }}"/>
    </div>
  </div>
  <div class="content">
    @if (session('success'))
      <div class="alert alert-success" style="max-width: 600px; margin-bottom: 20px;">
        {{ session('success') }}
      </div>
    @endif
    <h1>Добро пожаловать, {{ Auth::user()->first_name }}!</h1>
    <div class="cards">
      <div class="card">
        <h3>Моя подписка</h3>
        <p>Вы подписаны на материалы для 1 класса</p>
      </div>
      <div class="card">
        <h3>Кладовая идей</h3>
        <p>Материалы доступны всем пользователям</p>
      </div>
      <div class="card">
        <h3>Портфолио</h3>
        <p>Ваши награды и сертификаты</p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
@endpush
