@extends('layouts.app')

@section('title', $subject->title . ' — ' . $level->title . ' — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
<style>
  .topics-accordion {
    margin-top: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .topic-accordion-item {
    border: 1px solid #e2e2e2;
    border-radius: 14px;
    background: #fff;
    overflow: hidden;
  }
  .topic-accordion-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px 16px;
    cursor: pointer;
    user-select: none;
    transition: background 0.08s ease, border-color 0.08s ease;
    border: none;
    width: 100%;
    text-align: left;
    font: inherit;
  }
  .topic-accordion-head:hover {
    background: #f5fbff;
  }
  .topic-accordion-item.is-expanded .topic-accordion-head {
    background: #eaf6ff;
    border-bottom: 1px solid #e2e2e2;
  }
  .topic-accordion-head.is-disabled {
    opacity: 0.55;
    cursor: not-allowed;
    background: #f8f8f8;
  }
  .topic-accordion-head .topic-head-content {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
  }
  .topic-accordion-head .topic-number {
    font-weight: 800;
    min-width: 22px;
  }
  .topic-accordion-head .topic-title {
    font-weight: 600;
  }
  .topic-accordion-head .topic-badge {
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid #e2e2e2;
    background: #fff;
    color: #666;
  }
  .topic-accordion-head .topic-chevron {
    font-size: 14px;
    color: #666;
    transition: transform 0.2s ease;
  }
  .topic-accordion-item.is-expanded .topic-chevron {
    transform: rotate(180deg);
  }
  .topic-accordion-body {
    display: none;
    padding: 16px;
    background: #fafbfc;
  }
  .topic-accordion-item.is-expanded .topic-accordion-body {
    display: block;
  }
  .topic-accordion-body .files-list {
    display: grid;
    margin-top: 16px;
  }
  .topic-accordion-body .file-card {
    padding: 14px 16px;
    border: 1px solid #e2e2e2;
    border-radius: 12px;
    background: #fff;
  }
  .topic-accordion-body .file-card__top {
    margin-bottom: 0;
    align-items: center;
  }
  .topic-accordion-body .hint-box {
    margin: 0 0 12px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px dashed #cfcfcf;
    background: #fff;
    font-size: 14px;
    color: #555;
  }
  .topic-accordion-body .lesson-description {
    margin-top: 16px;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid #e2e2e2;
    background: #fff;
    font-size: 14px;
    line-height: 1.5;
  }
</style>
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => $level->id])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => site_lang('lk_dashboard|crumb_home', 'Главная'), 'url' => url('/')],
      ['label' => site_lang('lk_dashboard|crumb_cabinet', 'Кабинет'), 'url' => route('dashboard')],
      ['label' => $level->title, 'url' => route('subjects.index', ['level' => $level->id])],
      ['label' => $subject->title, 'url' => null],
    ],
  ])
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
            Нажмите на тему, чтобы развернуть и увидеть материалы. Развёрнутой может быть только одна тема.
            Если у темы нет загруженных файлов, она отображается неактивной.
          </p>
          @if(empty($hasAccess))
            <p class="muted" style="color: #c62828;">
              У вас нет активной подписки на этот уровень — темы закрыты. <a href="{{ route('subscriptions.index') }}">Оформить подписку</a>
            </p>
          @endif
        </div>
        <div class="subpage-top__actions">
          <a class="btn btn-secondary" href="{{ route('subjects.index', ['level' => $level->id]) }}">← Назад к предметам</a>
        </div>
      </div>

      <div class="field full azbuka-search">
        <label for="topicsSearch">Поиск темы по номеру или ключевым словам</label>
        <input id="topicsSearch" type="search" placeholder="Например: 1, школа, речь, предложение..." autocomplete="off"/>
      </div>

      <div id="topicsAccordion" class="topics-accordion" data-materials-url="{{ route('subjects.materials', ['level' => $levelSlug, 'subject' => $subjectSlug, 'topic' => '__topic__']) }}" aria-label="Список тем"></div>
      <div id="topicsEmpty" class="search-empty" hidden>Ничего не найдено. Попробуйте другое ключевое слово.</div>
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

  const TOPICS = @json($topicsPayload);

  const accordionEl = document.getElementById('topicsAccordion');
  const emptyEl = document.getElementById('topicsEmpty');
  const searchInput = document.getElementById('topicsSearch');
  const materialsUrlTemplate = accordionEl ? accordionEl.dataset.materialsUrl : '';

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

    accordionEl.innerHTML = '';
    if (emptyEl) emptyEl.hidden = filtered.length > 0;

    filtered.forEach((topic) => {
      const item = document.createElement('div');
      item.className = 'topic-accordion-item';
      item.dataset.topicId = topic.id;
      if (topic.id === activeTopicId) {
        item.classList.add('is-expanded');
      }

      const head = document.createElement('button');
      head.type = 'button';
      head.className = 'topic-accordion-head';
      if (topic.is_blocked) {
        head.classList.add('is-disabled');
      }
      const badge = topic.is_blocked
        ? '<span class="topic-badge" title="Закрыто">🔒</span>'
        : '<span class="topic-badge" title="Материалы доступны">📎</span>';
      const numberHtml = topic.number ? ('<span class="topic-number">' + topic.number + '</span>') : '';
      head.innerHTML =
        '<span class="topic-head-content">' +
          numberHtml +
          '<span class="topic-title">' + topic.title + '</span>' +
          badge +
        '</span>' +
        '<span class="topic-chevron" aria-hidden="true">▼</span>';

      const body = document.createElement('div');
      body.className = 'topic-accordion-body';
      body.setAttribute('role', 'region');
      body.setAttribute('aria-label', 'Материалы к теме');
      body.innerHTML = '<div class="topic-body-inner"></div>';

      head.addEventListener('click', function() {
        if (topic.is_blocked) return;
        const wasExpanded = activeTopicId === topic.id;
        activeTopicId = wasExpanded ? null : topic.id;
        accordionEl.querySelectorAll('.topic-accordion-item').forEach(function(el) {
          el.classList.remove('is-expanded');
        });
        if (!wasExpanded) {
          item.classList.add('is-expanded');
          const inner = body.querySelector('.topic-body-inner');
          inner.innerHTML = '<div class="hint-box">Загрузка материалов...</div>';
          fetchMaterials(topic.id).then(function(result) {
            renderMaterialsInto(inner, result.items, topic, result.noAccess);
          });
        }
      });

      item.appendChild(head);
      item.appendChild(body);
      accordionEl.appendChild(item);

      if (topic.id === activeTopicId) {
        const inner = body.querySelector('.topic-body-inner');
        inner.innerHTML = '<div class="hint-box">Загрузка материалов...</div>';
        fetchMaterials(topic.id).then(function(result) {
          renderMaterialsInto(inner, result.items, topic, result.noAccess);
        });
      }
    });
  }

  function buildMaterialsUrl(topicId) {
    if (!materialsUrlTemplate) return null;
    return materialsUrlTemplate.replace('__topic__', String(topicId));
  }

  function fetchMaterials(topicId) {
    const url = buildMaterialsUrl(topicId);
    if (!url) return Promise.resolve({ items: [], noAccess: false });
    return fetch(url, {
      headers: {
        'Accept': 'application/json',
      },
    })
      .then((response) => {
        return response.json().then((data) => ({
          items: data.materials || [],
          noAccess: response.status === 403,
        }));
      })
      .catch(() => ({ items: [], noAccess: false }));
  }

  function viewerUrl(path) {
    return '{{ route('viewer.show') }}?doc=' + encodeURIComponent(path);
  }

  function renderMaterialsInto(container, items, topic, noAccess) {
    container.innerHTML = '';

    if (noAccess) {
      container.innerHTML = '<div class="hint-box">Нет доступа к материалам. Оформите подписку на этот уровень.</div>';
      if (topic && topic.text_html) {
        const desc = document.createElement('div');
        desc.className = 'lesson-description';
        desc.innerHTML = topic.text_html;
        container.appendChild(desc);
      }
      return;
    }

    if (!items.length) {
      container.innerHTML = '<div class="hint-box">К этой теме пока нет загруженных файлов.</div>';
      if (topic && topic.text_html) {
        const desc = document.createElement('div');
        desc.className = 'lesson-description';
        desc.innerHTML = topic.text_html;
        container.appendChild(desc);
      }
      return;
    }

    if (topic && topic.text_html) {
      const desc = document.createElement('div');
      desc.className = 'lesson-description';
      desc.innerHTML = topic.text_html;
      container.appendChild(desc);
    }

    const filesList = document.createElement('div');
    filesList.className = 'files-list';
    container.appendChild(filesList);

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
      if (!fileUrl || (topic && topic.is_blocked)) return '';

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
      return '<div class="file-action-group">' + group.join('') + '</div>';
    }

    items.forEach(function(item) {
      const card = document.createElement('div');
      card.className = 'file-card';
      const actions = buildFileActions(item.pdf_url) + buildFileActions(item.zip_url);
      card.innerHTML =
        '<div class="file-card__top">' +
          '<div class="file-name">' + item.title + '</div>' +
          '<div class="card-actions file-actions">' + actions + '</div>' +
        '</div>';
      filesList.appendChild(card);
    });
  }

  function init() {
    if (!accordionEl) return;
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
