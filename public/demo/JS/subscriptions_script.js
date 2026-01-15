/**
 * subscriptions_script.js
 *
 * Страница: subscriptions.html (Подписки)
 *
 * Для разработчиков (новичков):
 * - ВНИЗУ есть массивы SUBSCRIPTIONS и TARIFFS — это "данные", которые позже можно брать из БД/API.
 * - Скрипт рендерит список подписок и тарифов в HTML (в контейнеры #subsList и #tariffsList).
 * - При изменении чекбоксов/радиокнопок автоматически пересчитывается итоговая сумма.
 *
 * Как подключить к бэкенду:
 * 1) Вместо констант SUBSCRIPTIONS/TARIFFS получить JSON через fetch('/api/subscriptions') и fetch('/api/tariffs')
 * 2) По кнопке "Оформить подписку" отправлять orderPayload на сервер: POST /api/checkout
 * 3) Сервер создаёт платёж и возвращает redirect URL или payment token.
 */

(function () {
  "use strict";

  // ---------- Утилиты ----------
  function formatRUB(value) {
    const n = Math.round(value);
    // Простое форматирование "1 296 ₽"
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " ₽";
  }

  function $(sel) {
    return document.querySelector(sel);
  }

  // ---------- Данные (пример) ----------
  // Позже это будет приходить из БД/API
  const SUBSCRIPTIONS = [
    { id: "grade1",  title: "1 класс",                   link: "sub_1.html" },
    { id: "grade2",  title: "2 класс",                   link: "sub_2.html" },
    { id: "grade3",  title: "3 класс",                   link: "sub_3.html" },
    { id: "grade4",  title: "4 класс",                   link: "sub_4.html" },
    { id: "pre",     title: "Дошкольники",               link: "sub_preschool.html" },
    { id: "extra",   title: "Внеурочная деятельность",   link: "sub_extracurricular.html" },
    { id: "train",   title: "Обучение",                  link: "sub_training.html" },
  ];

  const TARIFFS = [
    { id: "m1",  title: "1 месяц",   months: 1,  price: 224,  note: "" },
    { id: "m3",  title: "3 месяца",  months: 3,  price: 648,  note: "(по 216 ₽/мес.)" },
    { id: "m6",  title: "6 месяцев", months: 6,  price: 1296, note: "(по 216 ₽/мес.)" },
    { id: "m12", title: "12 месяцев",months: 12, price: 2592, note: "(по 216 ₽/мес.)" },
  ];

  // Правила скидок:
  // 1 подписка — 0%
  // 2 подписки — 10%
  // 3+ (но не все) — 15%
  // все подписки (7) — 20%
  function getDiscountPercent(selectedCount) {
    if (selectedCount >= SUBSCRIPTIONS.length) return 20;
    if (selectedCount >= 3) return 15;
    if (selectedCount === 2) return 10;
    return 0;
  }

  // ---------- Состояние ----------
  const LS_KEY = "sovushka_subscriptions_state_v1";
  const state = {
    selectedSubs: new Set(),
    tariffId: "m3", // по умолчанию 3 месяца (можно поменять)
  };

  function loadState() {
    try {
      const raw = localStorage.getItem(LS_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed.selectedSubs)) {
        state.selectedSubs = new Set(parsed.selectedSubs);
      }
      if (typeof parsed.tariffId === "string") {
        state.tariffId = parsed.tariffId;
      }
    } catch (e) {
      // Если localStorage недоступен — просто работаем без сохранения
      console.warn("Cannot load subscriptions state:", e);
    }
  }

  function saveState() {
    try {
      localStorage.setItem(
        LS_KEY,
        JSON.stringify({
          selectedSubs: Array.from(state.selectedSubs),
          tariffId: state.tariffId,
        })
      );
    } catch (e) {
      console.warn("Cannot save subscriptions state:", e);
    }
  }

  // ---------- Рендер ----------
  function renderSubscriptions() {
    const container = $("#subsList");
    if (!container) return;
    container.innerHTML = "";

    SUBSCRIPTIONS.forEach((s) => {
      const row = document.createElement("div");
      row.className = "sub-option";
      row.setAttribute("data-sub-id", s.id);

      // Чекбокс
      const cbId = "sub_" + s.id;

      row.innerHTML = `
        <label class="sub-left" for="${cbId}">
          <input class="sub-checkbox" id="${cbId}" type="checkbox" ${state.selectedSubs.has(s.id) ? "checked" : ""}/>
          <span class="sub-title">${s.title}</span>
        </label>
        <a class="btn btn-secondary btn-sm" href="${s.link}">Посмотреть</a>
      `;

      // Слушатель на чекбокс
      const cb = row.querySelector(".sub-checkbox");
      cb.addEventListener("change", () => {
        if (cb.checked) state.selectedSubs.add(s.id);
        else state.selectedSubs.delete(s.id);
        saveState();
        recalc();
      });

      container.appendChild(row);
    });
  }

  function renderTariffs() {
    const container = $("#tariffsList");
    if (!container) return;
    container.innerHTML = "";

    TARIFFS.forEach((t) => {
      const row = document.createElement("label");
      row.className = "tariff-option";
      const rid = "tariff_" + t.id;

      row.innerHTML = `
        <input class="tariff-radio" id="${rid}" name="tariff" type="radio" value="${t.id}" ${state.tariffId === t.id ? "checked" : ""}/>
        <span class="tariff-main">
          <span class="tariff-title">${t.title}</span>
          <span class="tariff-note">${t.note || ""}</span>
        </span>
        <span class="tariff-price">${formatRUB(t.price)}</span>
      `;

      const radio = row.querySelector(".tariff-radio");
      radio.addEventListener("change", () => {
        state.tariffId = t.id;
        saveState();
        recalc();
      });

      container.appendChild(row);
    });
  }

  // ---------- Расчёт ----------
  function recalc() {
    const count = state.selectedSubs.size;
    const tariff = TARIFFS.find((t) => t.id === state.tariffId) || null;

    const pricePerSub = tariff ? tariff.price : 0;
    const subtotal = count * pricePerSub;

    const discountPercent = getDiscountPercent(count);
    const discount = Math.round(subtotal * (discountPercent / 100));
    const total = subtotal - discount;

    // Обновить UI
    $("#sumCount").textContent = String(count);
    $("#sumTariff").textContent = tariff ? `${tariff.title} (${formatRUB(tariff.price)})` : "—";
    $("#sumSubtotal").textContent = formatRUB(subtotal);
    $("#sumDiscount").textContent = discountPercent > 0 ? `−${formatRUB(discount)} (${discountPercent}%)` : "0 ₽";
    $("#sumTotal").textContent = formatRUB(total);

    // Доступность кнопки оплаты
    const payBtn = $("#payBtn");
    if (payBtn) payBtn.disabled = !(count > 0 && !!tariff);

    // Чекбоксы скидок (справа)
    const disc2 = $("#disc2");
    const disc3 = $("#disc3");
    const discAll = $("#discAll");
    if (disc2) disc2.checked = discountPercent === 10;
    if (disc3) disc3.checked = discountPercent === 15;
    if (discAll) discAll.checked = discountPercent === 20;

    const hint = $("#discountHint");
    if (hint) {
      if (count <= 1) hint.textContent = "1 подписка — выгодных предложений нет";
      else if (discountPercent === 10) hint.textContent = "Активирована скидка 10% за 2 подписки";
      else if (discountPercent === 15) hint.textContent = "Активирована скидка 15% за 3+ подписки";
      else if (discountPercent === 20) hint.textContent = "Активирована скидка 20% за все подписки";
      else hint.textContent = "";
    }

    // Подготовка "payload" заказа — пригодится для бэкенда
    const orderPayload = {
      subscriptions: Array.from(state.selectedSubs),
      tariffId: tariff ? tariff.id : null,
      count,
      pricePerSubscription: pricePerSub,
      subtotal,
      discountPercent,
      discount,
      total,
      currency: "RUB",
      // Здесь можно добавить userId, промокод, период и т.д.
    };

    // Debug блок (скрыт), можно включить при отладке
    const dbg = $("#debugOrder");
    if (dbg) dbg.textContent = JSON.stringify(orderPayload, null, 2);

    // Сохраним последний payload (на будущее, например, перейти на оплату)
    try {
      localStorage.setItem("sovushka_last_order_payload", JSON.stringify(orderPayload));
    } catch (e) {}
  }

  // ---------- Оплата (заглушка) ----------
  function bindPayButton() {
    const btn = $("#payBtn");
    if (!btn) return;

    btn.addEventListener("click", () => {
      // В реальном проекте тут:
      // - POST /api/checkout (orderPayload)
      // - получить ссылку оплаты и сделать window.location.href = paymentUrl
      // Сейчас — демо:
      const last = localStorage.getItem("sovushka_last_order_payload");
      console.log("Checkout payload (demo):", last);
      alert("Демо: заказ сформирован. Подключение оплаты будет добавлено позже.");
    });
  }

  // ---------- Инициализация ----------
  function init() {
    // Этот скрипт должен работать только на странице subscriptions.html
    if (!document.getElementById("subscriptionsApp")) return;

    loadState();
    renderSubscriptions();
    renderTariffs();
    bindPayButton();
    recalc();
  }

  // DOMContentLoaded нам не нужен, потому что script подключён с defer
  init();
})();
