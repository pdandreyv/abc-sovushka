@extends('layouts.app')

@section('title', $level->title . ' — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => $level->id])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => site_lang('lk_dashboard|crumb_home', 'Главная'), 'url' => url('/')],
      ['label' => $level->title, 'url' => null],
    ],
  ])
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

      @if(!empty($level->demo_file))
      <div class="demo-lesson">
        <h3>Демо-уроки (можно скачать бесплатно)</h3>
        <p class="muted">
          Этот блок помогает пользователю увидеть пример материалов по подписке «{{ $level->title }}».
          Позже сюда можно подгружать демо-уроки из базы данных.
        </p>
        <div class="card-actions">
          <a class="btn btn-primary" target="_blank" rel="noopener" download href="{{ asset('files/subscription_levels/' . $level->id . '/demo_file/' . $level->demo_file) }}">Скачать ZIP</a>
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
