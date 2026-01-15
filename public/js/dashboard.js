/* 
  Совушкина школа — общий JS для страниц кабинета

  Основной путь: /JS/dashboard_script.js
  Скрипт делает:
  - модальные окна (в т.ч. подтверждение выхода)
  - просмотр документов (через viewer.html)
  - лайки и описание на странице ideas.html
  - переключатели/мелкие интерактивные элементы

  Подсказка новичкам:
  - Главные функции: initLogoutButtons(), initPortfolioPage(), initIdeasPage()
  - В корне проекта может лежать копия этого файла для обратной совместимости.
*/
// Совушкина школа — общий скрипт личного кабинета
// -------------------------------------------------------------
// Для новичков-программистов:
// - Этот файл подключается ко всем страницам кабинета.
// - Здесь лежит "поведение": раскрытие меню подписок, сохранение профиля,
//   просмотр документов, лайки, модальные окна и подтверждение выхода.
// - Проект сейчас работает в статическом режиме (без сервера). Поэтому:
//   * профиль и лайки сохраняются в localStorage (внутри браузера пользователя)
//   * "общие лайки всех пользователей" можно сделать только после подключения backend API.
// -------------------------------------------------------------

/** Утилита: безопасно читаем JSON из localStorage */
function loadJSON(key, fallback = null) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : fallback;
  } catch (e) {
    return fallback;
  }
}

/** Утилита: безопасно пишем JSON в localStorage */
function saveJSON(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value));
  } catch (e) {
    // Если localStorage недоступен (редко), просто молчим
  }
}

/** Утилита: показать элемент на короткое время (toast/баннер) */
function showTemporarily(el, ms = 2000) {
  if (!el) return;
  el.hidden = false;
  window.setTimeout(() => (el.hidden = true), ms);
}

/** Утилита: получить параметр из URL */
function getQueryParam(name) {
  const p = new URLSearchParams(window.location.search);
  return p.get(name);
}

/* =============================================================
   1) Подменю "Подписки"
   ============================================================= */

function toggleSubmenu() {
  // Глобальная функция нужна, потому что в main_dashboard.html используется inline onclick="toggleSubmenu()"
  const submenuList = document.querySelector('.submenu-list');
  const toggleIcon = document.querySelector('.toggle-icon');
  if (!submenuList) return;

  submenuList.classList.toggle('open');
  if (toggleIcon) {
    toggleIcon.textContent = submenuList.classList.contains('open') ? '▲' : '▼';
  }
}

function initSubmenuToggle() {
  // На новых страницах используем атрибут data-submenu-toggle (без inline JS)
  const btn = document.querySelector('button[data-submenu-toggle]');
  if (!btn) return;

  btn.addEventListener('click', function () {
    toggleSubmenu();
  });
}

/* =============================================================
   2) Модальное окно (универсальное)
   Используем для:
   - "Описание" (Кладовая идей)
   - подтверждение "Выйти" (все страницы кабинета)
   ============================================================= */

function ensureModalExists() {
  // Если модалки нет в HTML — создадим её через JS (чтобы не дублировать разметку во всех страницах)
  let overlay = document.getElementById('modalOverlay');
  if (overlay) return;

  overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.id = 'modalOverlay';
  overlay.hidden = true;

  overlay.innerHTML = `
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <div class="modal-header">
        <div class="modal-title" id="modalTitle">Заголовок</div>
        <button type="button" class="modal-close" id="modalClose" aria-label="Закрыть">×</button>
      </div>
      <div class="modal-body" id="modalBody"></div>
      <div class="modal-actions" id="modalActions" hidden>
        <button type="button" class="btn btn-secondary" id="modalCancel">Остаться</button>
        <button type="button" class="btn btn-primary" id="modalConfirm">Выйти</button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);
}

function openModal({ title = '', bodyHTML = '', showActions = false, onConfirm = null } = {}) {
  ensureModalExists();

  const overlay = document.getElementById('modalOverlay');
  const titleEl = document.getElementById('modalTitle');
  const bodyEl = document.getElementById('modalBody');
  const actions = document.getElementById('modalActions');
  const btnClose = document.getElementById('modalClose');
  const btnCancel = document.getElementById('modalCancel');
  const btnConfirm = document.getElementById('modalConfirm');

  if (!overlay || !titleEl || !bodyEl) return;

  titleEl.textContent = title;
  bodyEl.innerHTML = bodyHTML;

  // Управляем кнопками подтверждения
  if (actions) actions.hidden = !showActions;

  // Сбросим старые обработчики подтверждения
  if (btnConfirm) btnConfirm.onclick = null;
  if (btnCancel) btnCancel.onclick = null;

  if (showActions) {
    if (btnCancel) btnCancel.onclick = () => closeModal();
    if (btnConfirm) btnConfirm.onclick = () => {
      closeModal();
      if (typeof onConfirm === 'function') onConfirm();
    };
  }

  // Закрытие по кнопке крестик / клику на фон / ESC
  function onOverlayClick(e) {
    if (e.target === overlay) closeModal();
  }
  function onEsc(e) {
    if (e.key === 'Escape') closeModal();
  }

  overlay.hidden = false;
  overlay.addEventListener('click', onOverlayClick);
  document.addEventListener('keydown', onEsc);

  if (btnClose) {
    btnClose.onclick = () => closeModal();
  }

  // Запомним обработчики, чтобы снять их при закрытии
  overlay._cleanup = () => {
    overlay.removeEventListener('click', onOverlayClick);
    document.removeEventListener('keydown', onEsc);
  };
}

function closeModal() {
  const overlay = document.getElementById('modalOverlay');
  if (!overlay) return;
  overlay.hidden = true;

  // Снимаем обработчики (если есть)
  if (typeof overlay._cleanup === 'function') {
    overlay._cleanup();
    overlay._cleanup = null;
  }
}

/* =============================================================
   3) Кнопка "Выйти" + подтверждение
   ============================================================= */

function initLogoutButtons() {
  const logoutButtons = document.querySelectorAll('.user-logout-link[data-logout]');
  if (!logoutButtons.length) return;

  logoutButtons.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      openModal({
        title: 'ВНИМАНИЕ!',
        bodyHTML: '<p>Вы уверены, что хотите выйти из личного кабинета?</p>',
        showActions: true,
        onConfirm: () => {
          // Находим форму выхода и отправляем её
          const logoutForm = document.getElementById('logout-form') || document.querySelector('form[action*="logout"]');
          if (logoutForm) {
            logoutForm.submit();
          } else {
            // Если форма не найдена, редиректим на страницу входа
            window.location.href = '/login';
          }
        },
      });
    });
  });
}

/* =============================================================
   4) Страница нового пользователя: "Личные данные"
   - сохранение профиля
   - режим "редактировать"
   - "Сменить пароль" (показывает форму)
   ============================================================= */

function initProfileOnboardingPage() {
  const profileForm = document.getElementById('profileForm');
  if (!profileForm) return; // На других страницах формы нет

  const formFields = profileForm.querySelectorAll('input, select, textarea');
  const saveBtn = document.getElementById('saveProfileBtn');
  const editBtn = document.getElementById('editProfileBtn');
  const savedBanner = document.getElementById('profileSaved');

  const changePasswordLink = document.getElementById('changePasswordLink');
  const changePasswordPanel = document.getElementById('changePasswordPanel');
  const passwordForm = document.getElementById('passwordForm');
  const passwordSaved = document.getElementById('passwordSaved');
  const passwordError = document.getElementById('passwordError');

  const STORAGE_KEY = 'sovushka_profile_v1';

  function setReadOnly(isReadOnly) {
    formFields.forEach((el) => {
      // Можно исключать поля из блокировки: data-always-editable="true"
      if (el.dataset.alwaysEditable === 'true') return;
      el.disabled = isReadOnly;
    });

    if (saveBtn) saveBtn.style.display = isReadOnly ? 'none' : 'inline-flex';
    if (editBtn) editBtn.style.display = isReadOnly ? 'inline-flex' : 'none';
  }

  function fillForm(data) {
    if (!data) return;
    Object.keys(data).forEach((name) => {
      const field = profileForm.querySelector(`[name="${name}"], #${name}`);
      if (field) field.value = data[name];
    });
  }

  function collectFormData() {
    const data = {};
    formFields.forEach((el) => {
      if (!el.name && !el.id) return;
      const key = el.name || el.id;
      data[key] = el.value;
    });
    return data;
  }

  // При загрузке страницы: если это Laravel форма с данными, показываем режим "только чтение"
  // Проверяем, есть ли данные в форме (Laravel заполняет форму из базы данных)
  const hasData = Array.from(formFields).some(field => field.value && field.value.trim() !== '');
  
  // Если форма Laravel (имеет action), проверяем наличие данных
  if (profileForm.action && profileForm.method) {
    // Для Laravel: если есть данные, показываем режим "только чтение"
    if (hasData) {
      setReadOnly(true);
    } else {
      setReadOnly(false);
    }
  } else {
    // Для демо: используем localStorage
    const savedProfile = loadJSON(STORAGE_KEY, null);
    if (savedProfile) {
      fillForm(savedProfile);
      setReadOnly(true);
    } else {
      setReadOnly(false);
    }
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Если форма имеет action (Laravel форма), отправляем её на сервер
      if (profileForm.action && profileForm.method) {
        profileForm.submit();
      } else {
        // Иначе используем localStorage (для демо)
        const data = collectFormData();
        saveJSON(STORAGE_KEY, data);

        // Покажем подтверждение и заблокируем поля
        showTemporarily(savedBanner, 2000);
        setReadOnly(true);
      }
    });
  }

  if (editBtn) {
    editBtn.addEventListener('click', (e) => {
      e.preventDefault();
      setReadOnly(false);
    });
  }

  // "Сменить пароль" — показываем/скрываем панель
  if (changePasswordLink && changePasswordPanel) {
    changePasswordLink.addEventListener('click', (e) => {
      e.preventDefault();
      changePasswordPanel.hidden = !changePasswordPanel.hidden;
    });
  }

  // Смена пароля
  if (passwordForm) {
    passwordForm.addEventListener('submit', (e) => {
      // Если форма имеет action (Laravel форма), отправляем её на сервер
      if (passwordForm.action && passwordForm.method) {
        // Позволяем форме отправиться на сервер
        // Валидация будет на сервере
        return true;
      }

      // Иначе используем клиентскую валидацию (для демо)
      e.preventDefault();

      const newPass = document.getElementById('new_password')?.value || '';
      const repeatPass = document.getElementById('repeat_password')?.value || '';

      if (passwordError) passwordError.textContent = '';

      if (!newPass || newPass.length < 6) {
        if (passwordError) passwordError.textContent = 'Пароль должен быть не короче 6 символов.';
        return;
      }
      if (newPass !== repeatPass) {
        if (passwordError) passwordError.textContent = 'Пароли не совпадают.';
        return;
      }

      // В демо просто показываем успешный toast
      showTemporarily(passwordSaved, 2000);
      if (changePasswordPanel) changePasswordPanel.hidden = true;

      // Очистим поля
      ['current_password', 'new_password', 'repeat_password'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
    });
  }
}

/* =============================================================
   5) Портфолио: кнопка "Посмотреть"
   ============================================================= */

function initPortfolioPage() {
  // Кнопки просмотра документов в портфолио/кладовой идей помечены data-view-doc
  const viewButtons = document.querySelectorAll('[data-view-doc]');
  if (!viewButtons.length) return;

  viewButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const doc = btn.getAttribute('data-view-doc');
      if (!doc) return;

      // Открываем viewer.html в новом окне/вкладке.
      // viewer.html сам подставит нужный документ по параметру doc.
      window.open(`viewer.html?doc=${encodeURIComponent(doc)}`, '_blank', 'noopener');
    });
  });
}

/* =============================================================
   6) Кладовая идей:
   - лайки (сердечко)
   - модалка "Описание"
   ============================================================= */

function initIdeasPage() {
  // Эта функция нужна только на странице ideas.html
  if (!document.body || !document.title.includes('Кладовая идей')) return;

  // Поиск по материалам (поле вверху страницы)
  initIdeasSearch();

  // ---- ЛАЙКИ ----
  // Храним в localStorage:
  // - sov_like_user::<id>  (true/false) — поставил ли лайк текущий пользователь
  // - sov_like_count::<id> (number) — счётчик (в демо локальный)
  const likeButtons = document.querySelectorAll('[data-like-id]');
  likeButtons.forEach((btn) => {
    const id = btn.getAttribute('data-like-id');
    const defaultLikes = Number(btn.getAttribute('data-default-likes') || '0');

    const userKey = `sov_like_user::${id}`;
    const countKey = `sov_like_count::${id}`;

    let liked = loadJSON(userKey, false);
    let count = loadJSON(countKey, null);
    if (count === null) count = defaultLikes;

    const countEl = document.querySelector(`[data-like-count="${id}"]`);
    function render() {
      btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
      if (countEl) countEl.textContent = String(count);
      btn.classList.toggle('is-liked', liked);
    }

    btn.addEventListener('click', () => {
      // toggle
      liked = !liked;
      count = Math.max(0, count + (liked ? 1 : -1));

      saveJSON(userKey, liked);
      saveJSON(countKey, count);
      render();
    });

    render();
  });

  // ---- ОПИСАНИЕ (модальное окно) ----
  const descButtons = document.querySelectorAll('[data-open-description]');
  descButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-open-description');
      const block = document.getElementById(`desc_${id}`);
      const title = 'Описание материала';

      openModal({
        title,
        bodyHTML: block ? block.innerHTML : '<p>Описание не найдено.</p>',
        showActions: false,
      });
    });
  });
}

/* =============================================================
   Инициализация: запускаем нужные функции после загрузки DOM
   ============================================================= */



/* =============================================================
   8) Кладовая идей — поиск по ключевым словам (ideas.html)
   Для новичков:
   - Мы читаем текст из названия карточки и описания (скрытый блок).
   - Пользователь вводит слово — карточки фильтруются “на лету”.
   ============================================================= */

function initIdeasSearch() {
  const searchInput = document.getElementById('ideasSearch');
  if (!searchInput) return; // не на странице ideas.html

  const cards = Array.from(document.querySelectorAll('.idea-card'));
  const emptyState = document.getElementById('ideasEmpty');

  function normalize(str) {
    return (str || '')
      .toString()
      .toLowerCase()
      .replace(/ё/g, 'е')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function getCardText(card) {
    const title = card.querySelector('.award-title')?.textContent || '';
    const desc = card.querySelector('.idea-description')?.textContent || '';
    const keywords = card.getAttribute('data-keywords') || '';
    return normalize([title, desc, keywords].join(' '));
  }

  const cardIndex = cards.map((c) => ({ el: c, text: getCardText(c) }));

  function applyFilter() {
    const q = normalize(searchInput.value);
    const terms = q ? q.split(' ').filter(Boolean) : [];

    let visibleCount = 0;

    cardIndex.forEach(({ el, text }) => {
      const ok = terms.every((t) => text.includes(t));
      el.style.display = ok ? '' : 'none';
      if (ok) visibleCount += 1;
    });

    if (emptyState) emptyState.hidden = visibleCount !== 0;
  }

  searchInput.addEventListener('input', applyFilter);
  applyFilter(); // применим на старте (если браузер восстановил текст)
}




/* ============================================================
   1 класс → Русский язык. Азбука (sub_1_RUS_A.html)
   ------------------------------------------------------------
   Как это работает:
   - Слева список тем (topic-list)
   - Справа список файлов выбранной темы (files-list) + описание
   - Неактивные темы — те, у которых пока нет загруженных файлов
   - Поиск фильтрует темы по номеру/названию/ключевым словам
   ============================================================ */
function initSub1AzbukaPage() {
  const searchInput = document.getElementById('azbukaSearch');
  const topicsRoot = document.getElementById('azbukaTopics');
  const emptyBox = document.getElementById('azbukaEmpty');
  const hintBox = document.getElementById('azbukaHint');
  const filesRoot = document.getElementById('azbukaFiles');
  const descRoot = document.getElementById('azbukaDescription');

  // Если мы не на странице Азбуки — просто выходим
  if (!searchInput || !topicsRoot || !filesRoot) return;

  // Данные для демо (позже легко заменить на загрузку из БД/API)
  // Вариант для сервера:
  // - Список тем приходит с сервера (id, title, keywords, files[], descriptionHtml)
  // - По клику на тему можно подгружать файлы отдельно (lazy load)
  const TOPICS = [
    {
      id: 1,
      number: 1,
      title: 'Здравствуй, школа!',
      keywords: 'школа осанка правила учебник знакомство история прописи',
      files: [
        { kind: 'pdf',  label: 'Презентация', path: 'files/sub_1/RUS_A/1/presentation.pdf' },
        { kind: 'zip',  label: 'Презентация (архив)', path: 'files/sub_1/RUS_A/1/presentation.zip' },
        { kind: 'jpg',  label: 'Творческое задание', path: 'files/sub_1/RUS_A/1/creative_task.jpg' },
      ],
      descriptionHtml: `
        <div><b>Русский язык. Азбука. 1 класс.</b> Горецкий В.Г., Кирюшкин В.А. и др.</div>
        <div>Издательство 2023 года (по ФОП)</div>
        <div>Учебник, 1 часть, с. 4</div>
        <hr style="border:none;border-top:1px solid #e2e2e2;margin:10px 0;">
        <div><b>Структура урока:</b></div>
        <ol style="margin:8px 0 0 18px;">
          <li>Правила поведения. Осанка.</li>
          <li>Знакомство с учебником</li>
          <li>Немного истории</li>
          <li>Письменная работа (прописи)</li>
          <li>Работа по теме урока</li>
          <li>Физминутка</li>
          <li>Продолжение работы по теме урока</li>
          <li>Письменная работа (прописи)</li>
          <li>Рефлексия</li>
        </ol>
        <div style="margin-top:10px;"><b>Приложение к уроку:</b></div>
        <ul>
          <li>Творческое задание</li>
        </ul>
        <div style="margin-top:10px;"><b>Соответствие страниц учебника прописям:</b></div>
        <div>Прописи № 1, с. 3–6.</div>
      `,
    },
    {
      id: 2,
      number: 2,
      title: 'Устная и письменная речь. Предложение',
      keywords: 'устная письменная речь предложение',
      files: [], // пока нет файлов → тема неактивна
      descriptionHtml: '',
    },
    {
      id: 3,
      number: 3,
      title: 'Кто любит трудиться, тому без дела не сидится. Предложение и слово',
      keywords: 'трудиться без дела предложение слово',
      files: [], // пока нет файлов → тема неактивна
      descriptionHtml: '',
    },
  ];

  let activeId = null;

  function normalize(str) {
    return (str || '').toString().trim().toLowerCase();
  }

  function matchesTopic(topic, query) {
    if (!query) return true;

    const q = normalize(query);
    const hay = normalize(`${topic.number} ${topic.title} ${topic.keywords}`);
    return hay.includes(q);
  }

  function openViewer(path) {
    // Viewer открывается в новой вкладке; doc можно передавать как относительный путь.
    const url = `viewer.html?doc=${encodeURIComponent(path)}`;
    window.open(url, '_blank', 'noopener');
  }

  function renderFiles(topic) {
    // Очистим правую колонку
    filesRoot.innerHTML = '';
    if (descRoot) {
      descRoot.hidden = true;
      descRoot.innerHTML = '';
    }
    if (hintBox) hintBox.hidden = true;

    // Если файлов нет — покажем аккуратную подсказку
    if (!topic || !Array.isArray(topic.files) || topic.files.length === 0) {
      if (hintBox) {
        hintBox.hidden = false;
        hintBox.textContent = 'К этой теме пока нет загруженных файлов.';
      }
      return;
    }

    const all = topic.files.slice();

    const norm = (s) => String(s || '').trim().toLowerCase();
    const isKind = (f, kind) => norm(f.kind) === norm(kind);

    // 1) "Презентация" (PDF) + ZIP-архив в ОДНУ строку
    // 2) Отдельный блок "Презентация (архив)" НЕ показываем
    // 3) "Творческое задание" — кнопки справа от заголовка
    const presPdf = all.find((f) => isKind(f, 'pdf') && norm(f.label) === 'презентация');
    const presZip = all.find((f) => isKind(f, 'zip') && norm(f.label).startsWith('презентация'));
    const creative = all.find((f) => ['jpg','jpeg','png','webp'].includes(norm(f.kind)) && norm(f.label).startsWith('творческое'));

    const handled = new Set();
    const markHandled = (f) => { if (f && f.path) handled.add(f.path); };

    function makeBtn({ text, variant = 'secondary', href = '#', onClick = null, download = false, targetBlank = true }) {
      const a = document.createElement('a');
      a.href = href;
      a.className = `btn ${variant === 'primary' ? 'btn-primary' : 'btn-secondary'}`;
      a.textContent = text;

      if (onClick) {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          onClick();
        });
      } else if (targetBlank) {
        a.target = '_blank';
        a.rel = 'noopener';
      }

      if (download) a.setAttribute('download', '');
      return a;
    }

    function makeTopCard(title, buttons) {
      const card = document.createElement('div');
      card.className = 'file-card';

      const top = document.createElement('div');
      top.className = 'file-card__top';
      // Чтобы не было лишнего "низа" у заголовка, когда кнопки справа
      top.style.alignItems = 'center';
      top.style.marginBottom = '0';

      const name = document.createElement('div');
      name.className = 'file-name';
      name.textContent = title;

      // Используем уже существующий стиль .card-actions (как в других разделах)
      const actions = document.createElement('div');
      actions.className = 'card-actions';

      buttons.forEach((b) => actions.appendChild(b));

      top.appendChild(name);
      top.appendChild(actions);

      card.appendChild(top);
      filesRoot.appendChild(card);
    }

    if (presPdf) {
      makeTopCard('Презентация', [
        makeBtn({ text: 'Посмотреть', onClick: () => openViewer(presPdf.path), targetBlank: false }),
        makeBtn({ text: 'Смотреть PDF', href: presPdf.path }),
        makeBtn({ text: 'Скачать PDF', variant: 'primary', href: presPdf.path, download: true, targetBlank: false }),
        ...(presZip ? [makeBtn({ text: 'Скачать ZIP', href: presZip.path, download: true, targetBlank: false })] : []),
      ]);
      markHandled(presPdf);
      markHandled(presZip);
    }

    if (creative) {
      makeTopCard('Творческое задание', [
        makeBtn({ text: 'Посмотреть', onClick: () => openViewer(creative.path), targetBlank: false }),
        makeBtn({ text: 'Скачать JPG', variant: 'primary', href: creative.path, download: true, targetBlank: false }),
      ]);
      markHandled(creative);
    }

    // Остальные файлы (если появятся) — рендерим по старой схеме
    all.forEach((f) => {
      if (!f || !f.path) return;
      if (handled.has(f.path)) return;

      const card = document.createElement('div');
      card.className = 'file-card';

      const top = document.createElement('div');
      top.className = 'file-card__top';

      const name = document.createElement('div');
      name.className = 'file-name';
      name.textContent = f.label || 'Файл';

      const type = document.createElement('div');
      type.className = 'file-type';
      type.textContent = (f.kind || '').toUpperCase();

      top.appendChild(name);
      top.appendChild(type);

      const actions = document.createElement('div');
      actions.className = 'card-actions';

      const canView = ['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(norm(f.kind));
      if (canView) {
        actions.appendChild(makeBtn({ text: 'Смотреть', onClick: () => openViewer(f.path) }));
      }

      actions.appendChild(makeBtn({ text: 'Скачать', variant: 'primary', href: f.path, download: true, targetBlank: true }));

      card.appendChild(top);
      card.appendChild(actions);
      filesRoot.appendChild(card);
    });

    // Описание
    if (descRoot && topic.descriptionHtml) {
      descRoot.innerHTML = topic.descriptionHtml;
      descRoot.hidden = false;
    }
  }


  function setActive(topicId) {
    activeId = topicId;

    // Подсветка активной темы
    const items = topicsRoot.querySelectorAll('.topic-item');
    items.forEach((el) => {
      const id = Number(el.getAttribute('data-topic-id'));
      el.classList.toggle('is-active', id === activeId);
    });

    const topic = TOPICS.find((t) => t.id === activeId);
    renderFiles(topic);
  }

  function renderTopics() {
    const query = normalize(searchInput.value);
    const visible = TOPICS.filter((t) => matchesTopic(t, query));

    topicsRoot.innerHTML = '';

    if (emptyBox) emptyBox.hidden = visible.length !== 0;

    visible.forEach((t) => {
      const hasFiles = Array.isArray(t.files) && t.files.length > 0;

      const item = document.createElement('div');
      item.className = 'topic-item' + (hasFiles ? '' : ' is-disabled');
      item.setAttribute('data-topic-id', String(t.id));

      const left = document.createElement('div');
      left.style.display = 'flex';
      left.style.gap = '10px';
      left.style.alignItems = 'baseline';

      const num = document.createElement('div');
      num.className = 'topic-number';
      num.textContent = String(t.number) + '.';

      const title = document.createElement('div');
      title.className = 'topic-title';
      title.textContent = t.title;

      left.appendChild(num);
      left.appendChild(title);

      const badge = document.createElement('div');
      badge.className = 'topic-badge';
     badge.textContent = hasFiles ? '📎' : '🔒';
badge.title = hasFiles ? 'Материалы доступны' : 'В работе';

      item.appendChild(left);
      item.appendChild(badge);

      if (hasFiles) {
        item.addEventListener('click', () => setActive(t.id));
      }

      topicsRoot.appendChild(item);
    });

    // Если активная тема скрылась фильтром — сбросим выбор
    if (activeId && !visible.some((t) => t.id === activeId)) {
      activeId = null;
      filesRoot.innerHTML = '';
      if (descRoot) {
        descRoot.hidden = true;
        descRoot.innerHTML = '';
      }
      if (hintBox) {
        hintBox.hidden = false;
        hintBox.textContent = 'Выберите тему слева, чтобы увидеть файлы.';
      }
    }
  }

  searchInput.addEventListener('input', renderTopics);
  renderTopics(); // стартовый рендер
}


document.addEventListener('DOMContentLoaded', function () {
  initSubmenuToggle();
  initLogoutButtons();

  initProfileOnboardingPage();
  initPortfolioPage(); // также работает и для "Кладовой идей" (кнопка "Посмотреть")
  initIdeasPage();
  initSub1AzbukaPage();
});
