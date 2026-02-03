@extends('layouts.app')

@section('title', $level->title . ' — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
<!-- ===== ЛЕВАЯ ПАНЕЛЬ (sidebar): навигация личного кабинета ===== -->
<div class="sidebar">
  <div>
    <img alt="Логотип" class="logo" src="{{ asset('images/logo.png') }}"/>
    <div class="user-name">{{ strtoupper(Auth::user()->first_name . ' ' . Auth::user()->last_name) }}</div>
    <div class="user-code">ID: {{ Auth::user()->user_code }}</div>
    <a href="#" class="user-logout-link" data-logout>{{ site_lang('lk_menu|logout', 'Выйти') }}</a>
    <form method="POST" action="{{ route('logout') }}" id="logout-form" style="display: none;">
      @csrf
    </form>
    <div class="menu">
      <button onclick="window.location.href='{{ route('profile.show') }}'" type="button">{{ site_lang('lk_menu|profile', 'Личные данные') }}</button>
      <button onclick="window.location.href='{{ route('dashboard') }}'" type="button">{{ site_lang('lk_menu|portfolio', 'Портфолио') }}</button>
      <button onclick="window.location.href='{{ route('subscriptions.index') }}'" type="button">{{ site_lang('lk_menu|subscriptions', 'Подписки') }}</button>
      <button onclick="window.location.href='{{ route('ideas.index') }}'" type="button">{{ site_lang('lk_menu|ideas', 'Кладовая идей') }}</button>
    </div>
  </div>
</div>

<!-- ===== ПРАВАЯ ЧАСТЬ (main): контент страницы ===== -->
<div class="main">
  <div class="header">
    <div class="header-title">{{ $level->title }}</div>
  </div>

  <div class="content">
    <div class="card">
      <h1>{{ $level->title }}</h1>
      <p class="muted">Выберите предмет. Внутри — темы и файлы к урокам (презентации, рабочие листы, конспекты и др.).</p>

      @if(empty($hasAccess))
        <div class="alert alert-warning" style="margin-bottom: 1rem;">
          У вас нет активной подписки на этот уровень. Темы и материалы будут закрыты до оформления подписки.
          <a href="{{ route('subscriptions.index') }}">Оформить подписку</a>
        </div>
      @endif

      @if($level->sort_order === 1)
      <div class="demo-lesson">
        <h3>Демо-уроки (можно скачать бесплатно)</h3>
        <p class="muted">
          Этот блок помогает пользователю увидеть пример материалов по подписке «1 класс».
          Позже сюда можно подгружать демо-уроки из базы данных.
        </p>
        <div class="demo-lesson__meta">Пример: презентация из раздела «Русский язык. Азбука»</div>
        <div class="card-actions">
          <a class="btn btn-primary" target="_blank" rel="noopener" download href="/demo/files/sub_1/RUS_A/1/presentation.zip">Скачать ZIP</a>
        </div>
      </div>
      @endif

      <div class="folder-list">
        @forelse($subjects as $subject)
          @php
            $subjectLink = $subject->link;
            $isAbsolute = $subjectLink && (str_starts_with($subjectLink, 'http://') || str_starts_with($subjectLink, 'https://') || str_starts_with($subjectLink, '/'));

            if (!$subjectLink) {
              $subjectLink = $subject->topics_count > 0
                ? route('subjects.show', ['level' => $level->id, 'subject' => $subject->id])
                : '/demo/sub_2.html';
            } elseif (!$isAbsolute) {
              $subjectLink = route('subjects.show', ['level' => $level->id, 'subject' => $subjectLink]);
            }
          @endphp
          <a class="folder-item" href="{{ $subjectLink }}">
            <span class="folder-icon">📁</span>
            <span class="folder-title">{{ $subject->title }}</span>
          </a>
        @empty
          <div class="card" style="grid-column: 1 / -1;">
            <p>Пока нет доступных предметов.</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
@endpush
