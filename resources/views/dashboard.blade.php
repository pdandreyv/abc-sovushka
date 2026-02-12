@extends('layouts.app')

@section('title', site_lang('lk_dashboard|page_title', 'Личный кабинет — Совушкина школа'))

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => 'dashboard'])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => site_lang('lk_dashboard|crumb_home', 'Главная'), 'url' => null],
    ],
  ])
  <div class="content">
    @if (session('success'))
      <div class="alert alert-success" style="max-width: 600px; margin-bottom: 20px;">
        {{ session('success') }}
      </div>
    @endif
    <h1>{{ site_lang('lk_dashboard|welcome', 'Добро пожаловать') }}, {{ Auth::user()->first_name }}!</h1>
    <div class="cards">
      <div class="card">
        <h3>{{ site_lang('lk_dashboard|card_subscriptions_title', 'Мои подписки') }}</h3>
        @if($subscriptionLevels->isNotEmpty())
          <p>{{ site_lang('lk_dashboard|card_subscriptions_list', 'Вы подписаны на:') }} {{ $subscriptionLevels->pluck('title')->join(', ') }}</p>
        @else
          <p>{{ site_lang('lk_dashboard|card_subscriptions_empty', 'Пока нет активных подписок.') }}</p>
        @endif
      </div>
      <div class="card">
        <h3>{{ site_lang('lk_dashboard|card_ideas_title', 'Кладовая идей') }}</h3>
        @if($latestIdea ?? null)
          <p>{{ site_lang('lk_dashboard|card_ideas_latest', 'Последний материал:') }} <a href="{{ route('ideas.index') }}">{{ $latestIdea->title }}</a></p>
        @else
          <p>{{ site_lang('lk_dashboard|card_ideas_text', 'Материалы доступны всем пользователям') }} <a href="{{ route('ideas.index') }}">{{ site_lang('lk_dashboard|card_ideas_link', 'Перейти в раздел') }}</a></p>
        @endif
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
