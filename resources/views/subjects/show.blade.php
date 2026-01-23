@extends('layouts.app')

@section('title', $subject->title . ' — ' . $level->title . ' — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
<!-- ===== ЛЕВАЯ ПАНЕЛЬ (sidebar): навигация личного кабинета ===== -->
<div class="sidebar">
  <div>
    <img alt="Логотип" class="logo" src="{{ asset('images/logo.png') }}"/>
    <div class="user-name">{{ strtoupper(Auth::user()->first_name . ' ' . Auth::user()->last_name) }}</div>
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

<!-- ===== ОСНОВНАЯ ЧАСТЬ ===== -->
<div class="main">
  <div class="header">
    <div class="header-title">{{ $subject->title }}</div>
  </div>

  @php
    $levelLink = $level->link ?: (string) $level->id;
    $levelPath = trim(parse_url($levelLink, PHP_URL_PATH) ?? $levelLink, '/');
    $levelSlug = basename($levelPath);

    $subjectLink = $subject->link ?: (string) $subject->id;
    $subjectPath = trim(parse_url($subjectLink, PHP_URL_PATH) ?? $subjectLink, '/');
    $subjectSlug = basename($subjectPath);
  @endphp

  <div class="content">
    <div class="card">
      <div class="subpage-top">
        <div>
          <h1>{{ $subject->title }} ({{ $level->title }})</h1>
          <p class="muted">
            Здесь темы и файлы к урокам. Слева — список тем, справа — материалы выбранной темы.
            Если у темы нет загруженных файлов, она отображается неактивной.
          </p>
        </div>
        <div class="subpage-top__actions">
          <a class="btn btn-secondary" href="{{ route('subjects.index', ['level' => $level->id]) }}">← Назад к предметам</a>
        </div>
      </div>

      <div class="field full azbuka-search">
        <label for="topicsSearch">Поиск темы по номеру или ключевым словам</label>
        <input id="topicsSearch" type="search" placeholder="Например: 1, школа, речь, предложение..." autocomplete="off"/>
      </div>

      <div class="topics-layout" data-materials-url="{{ route('subjects.materials', ['level' => $levelSlug, 'subject' => $subjectSlug, 'topic' => '__topic__']) }}">
        <div class="topics-panel">
          <div class="panel-title">Темы</div>
          <div id="topicsList" class="topic-list" aria-label="Список тем"></div>
          <div id="topicsEmpty" class="search-empty" hidden>Ничего не найдено. Попробуйте другое ключевое слово.</div>
        </div>

        <div class="files-panel">
          <div class="panel-title">Материалы к теме</div>
          <div id="topicHint" class="hint-box">Выберите тему слева, чтобы увидеть файлы.</div>
          <div id="topicFiles" class="files-list"></div>
          <div id="topicDescription" class="lesson-description" hidden></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
@php
  $topicsPayload = $topicsData ?? collect();
@endphp
<script>
(function() {
  "use strict";

  const TOPICS = @json($topicsPayload).map((topic, index) => ({
    ...topic,
    number: index + 1,
  }));

  const listEl = document.getElementById('topicsList');
  const emptyEl = document.getElementById('topicsEmpty');
  const searchInput = document.getElementById('topicsSearch');
  const filesEl = document.getElementById('topicFiles');
  const hintEl = document.getElementById('topicHint');
  const descEl = document.getElementById('topicDescription');
  const layoutEl = document.querySelector('.topics-layout');
  const materialsUrlTemplate = layoutEl ? layoutEl.dataset.materialsUrl : '';

  let activeTopicId = null;

  function normalize(str) {
    return (str || '').toString().toLowerCase().trim();
  }

  function renderTopics(filterValue) {
    const keyword = normalize(filterValue);
    const filtered = TOPICS.filter((topic) => {
      if (!keyword) return true;
      return normalize(topic.number + ' ' + topic.title).includes(keyword);
    });

    listEl.innerHTML = '';
    emptyEl.hidden = filtered.length > 0;

    filtered.forEach((topic) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'topic-item';
      btn.dataset.topicId = topic.id;

      if (topic.is_blocked) {
        btn.classList.add('is-disabled');
      }
      if (topic.id === activeTopicId) {
        btn.classList.add('is-active');
      }

      const badge = topic.is_blocked
        ? '<span class="topic-badge" title="Закрыто">🔒</span>'
        : '<span class="topic-badge" title="Материалы доступны">📎</span>';

      btn.innerHTML =
        '<span class="topic-number">' + topic.number + '</span>' +
        '<span class="topic-title">' + topic.title + '</span>' +
        badge;

      btn.addEventListener('click', function() {
        if (topic.is_blocked) return;
        activeTopicId = topic.id;
        renderTopics(searchInput ? searchInput.value : '');

        hintEl.hidden = false;
        hintEl.textContent = 'Загрузка материалов...';
        filesEl.innerHTML = '';

        fetchMaterials(topic.id).then(function(items) {
          renderMaterials(items, topic);
        });
      });

      listEl.appendChild(btn);
    });
  }

  function buildMaterialsUrl(topicId) {
    if (!materialsUrlTemplate) return null;
    return materialsUrlTemplate.replace('__topic__', String(topicId));
  }

  function fetchMaterials(topicId) {
    const url = buildMaterialsUrl(topicId);
    if (!url) return Promise.resolve([]);
    return fetch(url, {
      headers: {
        'Accept': 'application/json',
      },
    })
      .then((response) => response.json())
      .then((data) => data.materials || [])
      .catch(() => []);
  }

  function renderMaterials(items, topic) {
    filesEl.innerHTML = '';

    if (!items.length) {
      hintEl.textContent = 'К этой теме пока нет загруженных файлов.';
      hintEl.hidden = false;
      if (descEl) {
        descEl.innerHTML = topic && topic.text_html ? topic.text_html : '';
        descEl.hidden = !topic || !topic.text_html;
      }
      return;
    }

    hintEl.hidden = true;
    if (descEl) {
      descEl.innerHTML = topic && topic.text_html ? topic.text_html : '';
      descEl.hidden = !topic || !topic.text_html;
    }

    function viewerUrl(path) {
      return '{{ route('viewer.show') }}?doc=' + encodeURIComponent(path);
    }

    items.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'file-card';

      const actions = [];
      if (topic && topic.is_blocked) {
        actions.push('<span class="topic-badge">Закрыто</span>');
      }

      function getExtension(path) {
        if (!path) return '';
        const clean = path.split('?')[0].split('#')[0];
        const parts = clean.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
      }

      function isPreviewable(ext) {
        return ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
      }

      function buildFileActions(fileUrl) {
        if (!fileUrl || (topic && topic.is_blocked)) return;

        const ext = getExtension(fileUrl);
        const label = ext ? ext.toUpperCase() : 'Файл';
        const group = [];

        if (isPreviewable(ext)) {
          group.push(
            '<a class="btn btn-secondary btn-icon" target="_blank" rel="noopener" href="' + viewerUrl(fileUrl) + '" title="Посмотреть">👁</a>'
          );
        }

        group.push(
          '<a class="btn btn-primary" target="_blank" rel="noopener" href="' + fileUrl + '" download>Скачать ' + label + '</a>'
        );

        actions.push('<div class="file-action-group">' + group.join('') + '</div>');
      }

      buildFileActions(item.pdf_url);
      buildFileActions(item.zip_url);

      card.innerHTML =
        '<div class="file-card__top">' +
          '<div class="file-name">' + item.title + '</div>' +
          '<div class="card-actions file-actions">' + actions.join('') + '</div>' +
        '</div>';

      filesEl.appendChild(card);

    });
  }

  function init() {
    if (!listEl) return;
    renderTopics('');

    if (searchInput) {
      searchInput.addEventListener('input', function() {
        renderTopics(this.value);
      });
    }
  }

  init();
})();
</script>
@endpush
