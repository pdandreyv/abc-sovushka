/* 
  Совушкина школа — общий JS для страниц кабинета
  Скрипт делает:
  - модальные окна (в т.ч. подтверждение выхода)
  - просмотр документов (через viewer.html)
  - лайки и описание на странице ideas.html
  - переключатели/мелкие интерактивные элементы
  - AJAX отправка формы смены пароля
*/

// Глобальные функции для работы с формой смены пароля
window.showPasswordErrors = function(errors) {
  const passwordError = document.getElementById('passwordError');
  const passwordSaved = document.getElementById('passwordSaved');
  const changePasswordPanel = document.getElementById('changePasswordPanel');
  const validationErrors = document.getElementById('validationErrors');
  
  if (!passwordError) return;
  
  const errorContent = document.getElementById('passwordErrorContent');
  if (!errorContent) return;
  
  if (passwordSaved) {
    passwordSaved.hidden = true;
    passwordSaved.classList.remove('show');
  }
  
  if (validationErrors) {
    validationErrors.hidden = true;
    validationErrors.classList.remove('show');
  }
  
  if (errors.length === 1) {
    errorContent.innerHTML = '❌ ' + errors[0];
  } else {
    errorContent.innerHTML = '❌ <strong>Ошибки:</strong><ul style="margin: 8px 0 0 0; padding-left: 20px;">' +
      errors.map(err => '<li>' + err + '</li>').join('') +
      '</ul>';
  }
  
  passwordError.hidden = false;
  passwordError.classList.add('show');
  passwordError.style.display = 'block';
  passwordError.style.opacity = '1';
  
  if (changePasswordPanel) {
    changePasswordPanel.hidden = false;
  }
  
  setTimeout(() => {
    passwordError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }, 100);
};

window.showPasswordSuccess = function(message) {
  const passwordSaved = document.getElementById('passwordSaved');
  const passwordError = document.getElementById('passwordError');
  
  if (!passwordSaved) return;
  
  if (passwordError) {
    passwordError.hidden = true;
    passwordError.classList.remove('show');
  }
  
  passwordSaved.textContent = '✅ ' + (message || 'Пароль успешно изменен!');
  passwordSaved.hidden = false;
  passwordSaved.classList.add('show');
  passwordSaved.style.display = 'inline-flex';
  passwordSaved.style.opacity = '1';
  
  setTimeout(() => {
    passwordSaved.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }, 100);
};

// Утилиты
function loadJSON(key, fallback = null) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw) : fallback;
  } catch (e) {
    return fallback;
  }
}

function saveJSON(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value));
  } catch (e) {
    // localStorage недоступен
  }
}

function showTemporarily(el, ms = 2000) {
  if (!el) return;
  el.hidden = false;
  window.setTimeout(() => (el.hidden = true), ms);
}

function getQueryParam(name) {
  const p = new URLSearchParams(window.location.search);
  return p.get(name);
}

// Подменю "Подписки"
function toggleSubmenu() {
  const submenuList = document.querySelector('.submenu-list');
  const toggleIcon = document.querySelector('.toggle-icon');
  if (!submenuList) return;

  submenuList.classList.toggle('open');
  if (toggleIcon) {
    toggleIcon.textContent = submenuList.classList.contains('open') ? '▲' : '▼';
  }
}

function initSubmenuToggle() {
  const btn = document.querySelector('button[data-submenu-toggle]');
  if (!btn) return;

  btn.addEventListener('click', toggleSubmenu);
}

// Модальное окно (универсальное)
function ensureModalExists() {
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

  if (actions) actions.hidden = !showActions;

  if (btnConfirm) btnConfirm.onclick = null;
  if (btnCancel) btnCancel.onclick = null;

  if (showActions) {
    if (btnCancel) btnCancel.onclick = () => closeModal();
    if (btnConfirm) btnConfirm.onclick = () => {
      closeModal();
      if (typeof onConfirm === 'function') onConfirm();
    };
  }

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

  overlay._cleanup = () => {
    overlay.removeEventListener('click', onOverlayClick);
    document.removeEventListener('keydown', onEsc);
  };
}

function closeModal() {
  const overlay = document.getElementById('modalOverlay');
  if (!overlay) return;
  overlay.hidden = true;

  if (typeof overlay._cleanup === 'function') {
    overlay._cleanup();
    overlay._cleanup = null;
  }
}

// Кнопка "Выйти" + подтверждение
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
          const logoutForm = document.getElementById('logout-form') || document.querySelector('form[action*="logout"]');
          if (logoutForm) {
            logoutForm.submit();
          } else {
            window.location.href = '/login';
          }
        },
      });
    });
  });
}

// Страница "Личные данные"
function initProfileOnboardingPage() {
  const profileForm = document.getElementById('profileForm');
  if (!profileForm) return;

  const formFields = profileForm.querySelectorAll('input, select, textarea');
  const saveBtn = document.getElementById('saveProfileBtn');
  const editBtn = document.getElementById('editProfileBtn');
  const savedBanner = document.getElementById('profileSaved');
  const changePasswordLink = document.getElementById('changePasswordLink');
  const changePasswordPanel = document.getElementById('changePasswordPanel');
  const passwordError = document.getElementById('passwordError');
  const passwordSaved = document.getElementById('passwordSaved');

  const STORAGE_KEY = 'sovushka_profile_v1';

  function setReadOnly(isReadOnly) {
    formFields.forEach((el) => {
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

  const hasData = Array.from(formFields).some(field => field.value && field.value.trim() !== '');
  
  if (profileForm.action && profileForm.method) {
    setReadOnly(hasData);
  } else {
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
      
      if (profileForm.action && profileForm.method) {
        profileForm.submit();
      } else {
        const data = collectFormData();
        saveJSON(STORAGE_KEY, data);
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

  if (changePasswordLink && changePasswordPanel) {
    changePasswordLink.addEventListener('click', (e) => {
      e.preventDefault();
      changePasswordPanel.hidden = !changePasswordPanel.hidden;
      if (changePasswordPanel.hidden && passwordError) {
        passwordError.hidden = true;
        passwordError.classList.remove('show');
      }
    });
  }

  if (passwordSaved && passwordSaved.classList.contains('show')) {
    passwordSaved.hidden = false;
    passwordSaved.style.display = 'inline-flex';
    passwordSaved.style.opacity = '1';
    
    if (changePasswordPanel) changePasswordPanel.hidden = true;
    ['current_password', 'new_password', 'repeat_password'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    setTimeout(() => {
      passwordSaved.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 200);
  }
  
  if (passwordError && passwordError.classList.contains('show')) {
    passwordError.hidden = false;
    passwordError.style.display = 'inline-flex';
    passwordError.style.opacity = '1';
    setTimeout(() => {
      passwordError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 200);
  }
}

// Портфолио: кнопка "Посмотреть"
function initPortfolioPage() {
  const viewButtons = document.querySelectorAll('[data-view-doc]');
  if (!viewButtons.length) return;

  viewButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const doc = btn.getAttribute('data-view-doc');
      if (!doc) return;
      window.open(`viewer.html?doc=${encodeURIComponent(doc)}`, '_blank', 'noopener');
    });
  });
}

// Кладовая идей: лайки и описание
function initIdeasPage() {
  if (!document.body || !document.title.includes('Кладовая идей')) return;

  initIdeasSearch();

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
      liked = !liked;
      count = Math.max(0, count + (liked ? 1 : -1));
      saveJSON(userKey, liked);
      saveJSON(countKey, count);
      render();
    });

    render();
  });

  const descButtons = document.querySelectorAll('[data-open-description]');
  descButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-open-description');
      const block = document.getElementById(`desc_${id}`);
      openModal({
        title: 'Описание материала',
        bodyHTML: block ? block.innerHTML : '<p>Описание не найдено.</p>',
        showActions: false,
      });
    });
  });
}

// Кладовая идей — поиск
function initIdeasSearch() {
  const searchInput = document.getElementById('ideasSearch');
  if (!searchInput) return;

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
  applyFilter();
}

// 1 класс → Русский язык. Азбука
function initSub1AzbukaPage() {
  const searchInput = document.getElementById('azbukaSearch');
  const topicsRoot = document.getElementById('azbukaTopics');
  const emptyBox = document.getElementById('azbukaEmpty');
  const hintBox = document.getElementById('azbukaHint');
  const filesRoot = document.getElementById('azbukaFiles');
  const descRoot = document.getElementById('azbukaDescription');

  if (!searchInput || !topicsRoot || !filesRoot) return;

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
      files: [],
      descriptionHtml: '',
    },
    {
      id: 3,
      number: 3,
      title: 'Кто любит трудиться, тому без дела не сидится. Предложение и слово',
      keywords: 'трудиться без дела предложение слово',
      files: [],
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
    const url = `viewer.html?doc=${encodeURIComponent(path)}`;
    window.open(url, '_blank', 'noopener');
  }

  function renderFiles(topic) {
    filesRoot.innerHTML = '';
    if (descRoot) {
      descRoot.hidden = true;
      descRoot.innerHTML = '';
    }
    if (hintBox) hintBox.hidden = true;

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
      top.style.alignItems = 'center';
      top.style.marginBottom = '0';

      const name = document.createElement('div');
      name.className = 'file-name';
      name.textContent = title;

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

    if (descRoot && topic.descriptionHtml) {
      descRoot.innerHTML = topic.descriptionHtml;
      descRoot.hidden = false;
    }
  }

  function setActive(topicId) {
    activeId = topicId;

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
  renderTopics();
}

// Автоматическое скрытие toast-уведомлений
function initToastAutoHide() {
  setTimeout(() => {
    const toasts = document.querySelectorAll('.toast.show');
    toasts.forEach((toast) => {
      if (toast.id === 'passwordSaved' || toast.id === 'passwordError') {
        return;
      }
      
      const isError = toast.classList.contains('toast-error');
      const delay = isError ? 5000 : 4000;
      
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
          toast.hidden = true;
          toast.style.display = 'none';
        }, 180);
      }, delay);
    });
  }, 300);
}

// Прокрутка к первой ошибке в форме
function scrollToFirstError() {
  const firstError = document.querySelector('.input-error, .error-text');
  if (firstError) {
    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    const input = firstError.closest('.field')?.querySelector('input, select, textarea');
    if (input) {
      setTimeout(() => input.focus(), 300);
    }
  }
}

// Делегирование событий для формы смены пароля на уровне document
document.addEventListener('submit', async function(e) {
  const form = e.target;
  
  if (form && (form.id === 'passwordForm' || form.getAttribute('data-ajax-form') === 'true')) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    e.cancelBubble = true;
    
    const passwordForm = document.getElementById('passwordForm');
    const passwordSaved = document.getElementById('passwordSaved');
    const passwordError = document.getElementById('passwordError');
    const changePasswordPanel = document.getElementById('changePasswordPanel');
    const validationErrors = document.getElementById('validationErrors');
    
    if (!passwordForm) return false;
    
    if (passwordSaved) {
      passwordSaved.hidden = true;
      passwordSaved.classList.remove('show');
    }
    if (passwordError) {
      passwordError.hidden = true;
      passwordError.classList.remove('show');
    }
    if (validationErrors) {
      validationErrors.hidden = true;
      validationErrors.classList.remove('show');
    }

    const currentPassword = document.getElementById('current_password')?.value || '';
    const newPassword = document.getElementById('new_password')?.value || '';
    const repeatPassword = document.getElementById('repeat_password')?.value || '';

    const errors = [];

    if (!currentPassword) {
      errors.push('Введите текущий пароль.');
    }
    if (!newPassword) {
      errors.push('Введите новый пароль.');
    } else if (newPassword.length < 8) {
      errors.push('Пароль должен содержать минимум 8 символов.');
    }
    if (!repeatPassword) {
      errors.push('Повторите новый пароль.');
    } else if (newPassword && repeatPassword && newPassword !== repeatPassword) {
      errors.push('Пароли не совпадают.');
    }

    if (errors.length > 0) {
      if (typeof window.showPasswordErrors === 'function') {
        window.showPasswordErrors(errors);
      }
      return false;
    }

    const formData = new FormData(passwordForm);
    const submitButton = passwordForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton?.textContent;

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Сохранение...';
    }

    try {
      const response = await fetch(passwordForm.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        try {
          const errorData = await response.json();
          // Если есть сообщение об ошибке, используем его
          if (errorData.message) {
            throw { message: errorData.message, errors: errorData.errors || {} };
          }
          // Если есть ошибки валидации, формируем понятное сообщение
          if (errorData.errors) {
            const validationErrors = [];
            Object.keys(errorData.errors).forEach((field) => {
              const fieldErrors = Array.isArray(errorData.errors[field]) 
                ? errorData.errors[field] 
                : [errorData.errors[field]];
              validationErrors.push(...fieldErrors);
            });
            throw { message: validationErrors.length > 0 ? validationErrors[0] : 'Пожалуйста, исправьте ошибки в форме.', errors: errorData.errors };
          }
          throw errorData;
        } catch (jsonError) {
          // Если не удалось распарсить JSON, формируем понятное сообщение
          let userMessage = 'Произошла ошибка при изменении пароля.';
          if (response.status === 422) {
            userMessage = 'Текущий пароль введен неверно.';
          } else if (response.status === 401) {
            userMessage = 'Неверный текущий пароль.';
          } else if (response.status === 500) {
            userMessage = 'Произошла ошибка на сервере. Попробуйте еще раз позже.';
          }
          throw { message: userMessage, errors: {} };
        }
      }

      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text();
        throw new Error('Сервер вернул не JSON ответ. Возможно, произошла ошибка.');
      }

      const data = await response.json();

      if (data.success) {
        if (typeof window.showPasswordSuccess === 'function') {
          window.showPasswordSuccess(data.message);
        }
        if (changePasswordPanel) changePasswordPanel.hidden = true;
        ['current_password', 'new_password', 'repeat_password'].forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.value = '';
        });
      } else {
        const serverErrors = [];
        
        if (data.errors) {
          Object.keys(data.errors).forEach((field) => {
            const fieldErrors = Array.isArray(data.errors[field]) 
              ? data.errors[field] 
              : [data.errors[field]];
            serverErrors.push(...fieldErrors);
          });
        }
        
        if (data.message) {
          if (serverErrors.length > 0) {
            serverErrors.unshift(data.message);
          } else {
            serverErrors.push(data.message);
          }
        }
        
        if (data.error_type === 'current_password') {
          serverErrors.push('Неверный текущий пароль.');
        }
        
        if (serverErrors.length === 0) {
          serverErrors.push('Произошла ошибка при изменении пароля.');
        }
        
        if (typeof window.showPasswordErrors === 'function') {
          window.showPasswordErrors(serverErrors);
        }
      }
    } catch (error) {
      let errorMessages = [];
      
      // Если ошибка содержит объект с errors (валидация)
      if (error && typeof error === 'object' && error.errors) {
        Object.keys(error.errors).forEach((field) => {
          const fieldErrors = Array.isArray(error.errors[field]) 
            ? error.errors[field] 
            : [error.errors[field]];
          errorMessages.push(...fieldErrors);
        });
      }
      
      // Если есть общее сообщение об ошибке
      if (error && error.message) {
        if (errorMessages.length > 0) {
          // Добавляем общее сообщение в начало, если есть детальные ошибки
          errorMessages.unshift(error.message);
        } else {
          errorMessages.push(error.message);
        }
      }
      
      // Если нет сообщений, используем общее
      if (errorMessages.length === 0) {
        errorMessages.push('Произошла ошибка при изменении пароля. Пожалуйста, проверьте правильность введенных данных и попробуйте еще раз.');
      }
      
      if (typeof window.showPasswordErrors === 'function') {
        window.showPasswordErrors(errorMessages);
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalButtonText || 'Сохранить пароль';
      }
    }
    
    return false;
  }
}, { capture: true, passive: false });

document.addEventListener('DOMContentLoaded', function () {
  initSubmenuToggle();
  initLogoutButtons();
  initProfileOnboardingPage();
  initPortfolioPage();
  initIdeasPage();
  initSub1AzbukaPage();
  initToastAutoHide();
  
  if (document.querySelector('.toast-error.show, .input-error')) {
    setTimeout(scrollToFirstError, 100);
  }
});
