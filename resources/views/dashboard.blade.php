@extends('layouts.app')

@section('title', site_lang('lk_dashboard|page_title', 'Личный кабинет — Совушкина школа'))

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => 'dashboard'])

<div class="main">
  <!-- Основной контент страницы (шапка, хлебные крошки, карточки и т.д.) -->
  <div class="header">
    <div class="breadcrumbs">{{ site_lang('lk_dashboard|breadcrumbs', 'Главная / Кабинет') }}</div>
    <div class="header-icons">
      <img alt="Подписка" src="{{ asset('images/subscription_icon.png') }}"/>
      <a class="subscription-status subscription-status-link" href="{{ route('dashboard') }}">{{ site_lang('lk_dashboard|status', 'Осталось 5 дней подписки: продлить / отменить') }}</a>
      <img alt="Поддержка" src="{{ asset('images/support_icon.png') }}"/>
    </div>
  </div>
  <div class="content">
    @if (session('success'))
      <div class="alert alert-success" style="max-width: 600px; margin-bottom: 20px;">
        {{ session('success') }}
      </div>
    @endif
    <h1>{{ site_lang('lk_dashboard|welcome', 'Добро пожаловать') }}, {{ Auth::user()->first_name }}!</h1>
    <div class="cards">
      <div class="card">
        <h3>{{ site_lang('lk_dashboard|card_subscription_title', 'Моя подписка') }}</h3>
        <p>{{ site_lang('lk_dashboard|card_subscription_text', 'Вы подписаны на материалы для 1 класса') }}</p>
      </div>
      <div class="card">
        <h3>{{ site_lang('lk_dashboard|card_ideas_title', 'Кладовая идей') }}</h3>
        <p>{{ site_lang('lk_dashboard|card_ideas_text', 'Материалы доступны всем пользователям') }}</p>
      </div>
      <a href="{{ route('portfolio.index') }}" class="card" style="text-decoration: none; color: inherit;">
        <h3>{{ site_lang('lk_dashboard|card_portfolio_title', 'Портфолио') }}</h3>
        <p>{{ site_lang('lk_dashboard|card_portfolio_text', 'Ваши награды и сертификаты') }}</p>
      </a>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
@endpush
