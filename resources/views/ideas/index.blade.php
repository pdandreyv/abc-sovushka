@extends('layouts.app')

@section('title', 'Кладовая идей — Совушкина школа')

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
      <button onclick="window.location.href='{{ route('dashboard') }}'" type="button">Подписки</button>
      <button class="active" onclick="window.location.href='{{ route('ideas.index') }}'" type="button">Кладовая идей</button>
    </div>
  </div>
</div>

<!-- ===== ОСНОВНАЯ ЧАСТЬ (контент) ===== -->
<div class="main">
  <div class="header">
    <div class="breadcrumbs">Главная / Кабинет / Кладовая идей</div>
    <div class="header-icons">
      <img alt="Подписка" src="{{ asset('images/subscription_icon.png') }}"/>
      <div class="subscription-status">Подписок нет: материалы здесь доступны бесплатно</div>
      <img alt="Поддержка" src="{{ asset('images/support_icon.png') }}"/>
    </div>
  </div>

  <div class="content">
    <h1>Кладовая идей</h1>
    <p class="page-hint">
      Здесь собраны бесплатные материалы. Нажмите «Посмотреть», чтобы открыть крупный предпросмотр в новом окне,
      «Скачать», чтобы открыть файл для скачивания в новой вкладке (эта страница останется открытой),
      а «Описание» — чтобы прочитать подробности.
    </p>

    <!-- ===== Поиск по материалам ===== -->
    <div class="idea-search">
      <div class="field full">
        <label for="ideasSearch">Поиск по материалам</label>
        <input
          id="ideasSearch"
          type="search"
          placeholder="Введите ключевое слово (например: Новый год, кубик, разговоры...)"
          autocomplete="off"
        />
      </div>
    </div>

    <!-- Сообщение, если ничего не найдено -->
    <div class="search-empty" id="ideasEmpty" hidden>Ничего не найдено. Попробуйте другое слово.</div>

    <!-- Сетка карточек -->
    <div class="cards portfolio-grid ideas-grid">
      @forelse($ideas as $idea)
        <div class="card award-card idea-card" data-keywords="{{ strtolower(trim($idea->title . ' ' . ($idea->description_text ?? ''))) }}">
          <div class="award-thumb">
            <span class="award-badge badge-free">Бесплатно</span>

            <!-- Лайк -->
            <button 
              class="like-button {{ $idea->is_liked ? 'liked' : '' }}" 
              type="button" 
              data-idea-id="{{ $idea->id }}" 
              aria-pressed="{{ $idea->is_liked ? 'true' : 'false' }}" 
              aria-label="Поставить лайк"
            >
              <span class="like-heart">♥</span>
              <span class="like-count" data-idea-count="{{ $idea->id }}">{{ $idea->likes }}</span>
            </button>

            @if($idea->image)
              <img src="/_imgs/270x185/files/ideas/{{ $idea->id }}/image/{{ $idea->image }}" alt="{{ $idea->title }}" />
            @else
              <img src="{{ asset('images/placeholder.jpg') }}" alt="{{ $idea->title }}" />
            @endif
          </div>

          <div class="award-actions idea-actions">
            @if($idea->pdf_file)
              <button class="btn btn-secondary" type="button" data-view-doc="/files/{{ $idea->pdf_file }}">Посмотреть</button>
              <a class="btn btn-primary" href="/files/{{ $idea->pdf_file }}" target="_blank" rel="noopener">Скачать PDF</a>
            @endif
            @if($idea->zip_file)
              <a class="btn btn-secondary" href="/files/{{ $idea->zip_file }}" target="_blank" rel="noopener">Скачать ZIP</a>
            @endif
            <button class="btn btn-secondary" type="button" data-open-description="idea_{{ $idea->id }}">Описание</button>
          </div>

          <div class="award-title">{{ $idea->title }}</div>

          <!-- Скрытый блок с описанием -->
          <div class="idea-description" id="desc_idea_{{ $idea->id }}" hidden>
            {!! $idea->formatted_description !!}
          </div>
        </div>
      @empty
        <div class="card" style="grid-column: 1 / -1;">
          <p>Пока нет доступных материалов.</p>
        </div>
      @endforelse
    </div>
  </div>
</div>

<!-- ===== Универсальная модалка (Описание / Подтверждение выхода) ===== -->
<div class="modal-overlay" id="modalOverlay" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Заголовок</div>
      <button type="button" class="modal-close" id="modalClose" aria-label="Закрыть">×</button>
    </div>

    <div class="modal-body" id="modalBody"></div>

    <!-- Блок кнопок подтверждения (используется для "Выйти"). Для описаний скрыт. -->
    <div class="modal-actions" id="modalActions" hidden>
      <button type="button" class="btn btn-secondary" id="modalCancel">Остаться</button>
      <button type="button" class="btn btn-primary" id="modalConfirm">Выйти</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Инициализация поиска
  const searchInput = document.getElementById('ideasSearch');
  const emptyMessage = document.getElementById('ideasEmpty');
  const ideaCards = document.querySelectorAll('.idea-card');

  if (searchInput && ideaCards.length > 0) {
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase().trim();
      let foundCount = 0;

      ideaCards.forEach(function(card) {
        const keywords = (card.getAttribute('data-keywords') || '').toLowerCase();
        
        // Если поисковый запрос пустой, показываем все карточки
        if (searchTerm === '') {
          card.style.display = '';
          foundCount++;
          return;
        }
        
        // Проверяем, содержит ли keywords поисковый запрос
        if (keywords.includes(searchTerm)) {
          card.style.display = '';
          foundCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // Показываем сообщение, если ничего не найдено
      if (foundCount === 0 && searchTerm !== '') {
        emptyMessage.removeAttribute('hidden');
      } else {
        emptyMessage.setAttribute('hidden', '');
      }
    });
    
    // Обработка очистки поиска
    searchInput.addEventListener('search', function() {
      if (this.value === '') {
        ideaCards.forEach(function(card) {
          card.style.display = '';
        });
        emptyMessage.setAttribute('hidden', '');
      }
    });
  }

  // Обработка лайков
  const likeButtons = document.querySelectorAll('.like-button[data-idea-id]');
  likeButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      const ideaId = this.getAttribute('data-idea-id');
      const likeCountElement = this.querySelector('.like-count');
      const heartElement = this.querySelector('.like-heart');
      
      // Отправляем запрос на сервер
      fetch(`/ideas/${ideaId}/like`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Обновляем счетчик
          likeCountElement.textContent = data.likes_count;
          
          // Обновляем состояние кнопки
          if (data.liked) {
            this.classList.add('liked');
            this.setAttribute('aria-pressed', 'true');
            heartElement.style.color = '#ff0000';
          } else {
            this.classList.remove('liked');
            this.setAttribute('aria-pressed', 'false');
            heartElement.style.color = '';
          }
        }
      })
      .catch(error => {
        console.error('Ошибка при лайке:', error);
      });
    });
  });

  // Обработка модального окна описания
  const descriptionButtons = document.querySelectorAll('[data-open-description]');
  const modalOverlay = document.getElementById('modalOverlay');
  const modalTitle = document.getElementById('modalTitle');
  const modalBody = document.getElementById('modalBody');
  const modalClose = document.getElementById('modalClose');

  descriptionButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      const descriptionId = this.getAttribute('data-open-description');
      const descriptionElement = document.getElementById('desc_' + descriptionId);
      
      if (descriptionElement) {
        // Находим заголовок идеи
        const card = this.closest('.idea-card');
        const titleElement = card.querySelector('.award-title');
        const title = titleElement ? titleElement.textContent : 'Описание';
        
        modalTitle.textContent = title;
        modalBody.innerHTML = descriptionElement.innerHTML;
        modalOverlay.removeAttribute('hidden');
      }
    });
  });

  // Закрытие модального окна
  if (modalClose) {
    modalClose.addEventListener('click', function() {
      modalOverlay.setAttribute('hidden', '');
    });
  }

  if (modalOverlay) {
    modalOverlay.addEventListener('click', function(e) {
      if (e.target === modalOverlay) {
        modalOverlay.setAttribute('hidden', '');
      }
    });
  }

  // Обработка просмотра PDF
  const viewButtons = document.querySelectorAll('[data-view-doc]');
  viewButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      const pdfUrl = this.getAttribute('data-view-doc');
      if (pdfUrl) {
        window.open(pdfUrl, '_blank');
      }
    });
  });
});
</script>
@endpush
