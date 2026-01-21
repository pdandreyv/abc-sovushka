@extends('layouts.app')

@section('title', site_lang('lk_subscriptions|page_title', 'Подписки — Совушкина школа'))

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
      <button class="active" onclick="window.location.href='{{ route('subscriptions.index') }}'" type="button">{{ site_lang('lk_menu|subscriptions', 'Подписки') }}</button>
      <button onclick="window.location.href='{{ route('ideas.index') }}'" type="button">{{ site_lang('lk_menu|ideas', 'Кладовая идей') }}</button>
    </div>
  </div>
</div>

<!-- ===== ПРАВАЯ ЧАСТЬ (main): контент страницы ===== -->
<div class="main">
  <div class="header">
    <div class="breadcrumbs">{{ site_lang('lk_subscriptions|breadcrumbs', 'Главная / Кабинет / Подписки') }}</div>
    <div class="header-icons">
      <img alt="Подписка" src="{{ asset('images/subscription_icon.png') }}"/>
      <a class="subscription-status subscription-status-link" href="{{ route('subscriptions.index') }}">{{ site_lang('lk_subscriptions|status', 'Выбор подписок и тарифа') }}</a>
      <img alt="Поддержка" src="{{ asset('images/support_icon.png') }}"/>
    </div>
  </div>

  <div class="content">
    <h1>{{ site_lang('lk_subscriptions|heading', 'Подписки') }}</h1>
    <p class="page-hint">
      {{ site_lang('lk_subscriptions|hint', 'Выберите нужные подписки и тариф — итоговая сумма и скидка пересчитаются автоматически.') }}
    </p>

    <!-- ===== ТРИ ШАГА ВЫБОРА: подписки → тариф → скидка ===== -->
    <div id="subscriptionsApp" class="steps-grid">
      <!-- Шаг 1 -->
      <div class="step-card">
        <div class="step-title">
          <span class="step-num">1</span>
          <span>{{ site_lang('lk_subscriptions|step1', 'Выберите подписки') }}</span>
        </div>

        <!-- Список подписок -->
        <div id="subsList" class="subs-list">
          @foreach($levels as $level)
          <div class="sub-option" data-sub-id="{{ $level->slug }}">
            <label class="sub-left" for="sub_{{ $level->slug }}">
              <input class="sub-checkbox" id="sub_{{ $level->slug }}" type="checkbox" value="{{ $level->id }}"/>
              <span class="sub-title">{{ $level->title }}</span>
            </label>
            @if($level->link)
            <a class="btn btn-secondary btn-sm" href="{{ $level->link }}" target="_blank">{{ site_lang('lk_subscriptions|view', 'Посмотреть') }}</a>
            @endif
          </div>
          @endforeach
        </div>

        <div class="step-note">
          <small>{{ site_lang('lk_subscriptions|step1_note', 'Нажмите «Посмотреть», чтобы открыть страницу направления (заглушки для будущих разделов).') }}</small>
        </div>
      </div>

      <!-- Шаг 2 -->
      <div class="step-card">
        <div class="step-title">
          <span class="step-num">2</span>
          <span>{{ site_lang('lk_subscriptions|step2', 'Выберите тариф') }}</span>
        </div>

        <!-- Тарифы -->
        <div id="tariffsList" class="tariffs-list">
          @foreach($tariffs as $tariff)
          <label class="tariff-option">
            <input class="tariff-radio" id="tariff_{{ $tariff->id }}" name="tariff" type="radio" value="{{ $tariff->id }}" data-price="{{ $tariff->price }}" data-days="{{ $tariff->days }}" {{ $loop->first ? 'checked' : '' }}/>
            <span class="tariff-main">
              <span class="tariff-title">{{ $tariff->title }}</span>
              @if(!empty($tariff->price_phrase))
                <span class="tariff-note">({{ $tariff->price_phrase }})</span>
              @elseif($tariff->days == 30)
                <span class="tariff-note"></span>
              @else
                <span class="tariff-note">(по {{ number_format($tariff->price / ($tariff->days / 30), 0, ',', ' ') }} ₽/мес.)</span>
              @endif
            </span>
            <span class="tariff-price">{{ number_format($tariff->price, 0, ',', ' ') }} ₽</span>
          </label>
          @endforeach
        </div>

        <div class="step-note">
          <small>{{ site_lang('lk_subscriptions|step2_note', 'Цены указаны за 1 подписку. Итог зависит от количества выбранных подписок.') }}</small>
        </div>
      </div>

      <!-- Шаг 3 -->
      <div class="step-card">
        <div class="step-title">
          <span class="step-num">3</span>
          <span>{{ site_lang('lk_subscriptions|step3', 'Выгодные предложения') }}</span>
        </div>

        <!-- Скидки автоматически активируются в зависимости от количества выбранных подписок -->
        <div class="discounts-box">
          <label class="discount-row">
            <input disabled id="disc2" type="checkbox"/>
            <span>{{ site_lang('lk_subscriptions|discount_2', '2 подписки — −10%') }}</span>
          </label>
          <label class="discount-row">
            <input disabled id="disc3" type="checkbox"/>
            <span>{{ site_lang('lk_subscriptions|discount_3', '3+ подписки — −15%') }}</span>
          </label>
          <label class="discount-row">
            <input disabled id="discAll" type="checkbox"/>
            <span>{{ site_lang('lk_subscriptions|discount_all', 'Все подписки — −20%') }}</span>
          </label>
          <div id="discountHint" class="discount-hint">{{ site_lang('lk_subscriptions|discount_none', '1 подписка — выгодных предложений нет') }}</div>
        </div>

        <div class="step-note">
          <small>{{ site_lang('lk_subscriptions|step3_note', 'Скидка применяется автоматически и отображается в расчёте ниже.') }}</small>
        </div>
      </div>
    </div>

    <!-- ===== ИТОГ / ОПЛАТА ===== -->
    <div class="checkout-panel">
      <div class="checkout-left">
        <div class="checkout-line">
          <span>{{ site_lang('lk_subscriptions|summary_count', 'Выбрано подписок:') }}</span>
          <b id="sumCount">0</b>
        </div>
        <div class="checkout-line">
          <span>{{ site_lang('lk_subscriptions|summary_tariff', 'Тариф:') }}</span>
          <b id="sumTariff">—</b>
        </div>
        <div class="checkout-line">
          <span>{{ site_lang('lk_subscriptions|summary_subtotal', 'Стоимость:') }}</span>
          <b id="sumSubtotal">0 ₽</b>
        </div>
        <div class="checkout-line">
          <span>{{ site_lang('lk_subscriptions|summary_discount', 'Скидка:') }}</span>
          <b id="sumDiscount">0 ₽</b>
        </div>
        <div class="checkout-total">
          <span>{{ site_lang('lk_subscriptions|summary_total', 'Итого:') }}</span>
          <b id="sumTotal">0 ₽</b>
        </div>
      </div>

      <div class="checkout-right">
        <button id="payBtn" class="btn btn-primary btn-pay" type="button" disabled>{{ site_lang('lk_subscriptions|pay', 'Оформить подписку') }}</button>
        <div class="checkout-note">
          <small>{{ site_lang('lk_subscriptions|pay_note', 'Демо: оплата будет подключена позже (через бэкенд / платёжного провайдера).') }}</small>
        </div>
      </div>
    </div>

    <!-- Технический блок (скрытый): здесь удобно хранить "параметры заказа" для отправки на сервер -->
    <pre id="debugOrder" class="debug-order" hidden></pre>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
@php
  $uiTexts = [
    'discount_none' => site_lang('lk_subscriptions|discount_none', '1 подписка — выгодных предложений нет'),
    'discount_10' => site_lang('lk_subscriptions|discount_hint_10', 'Активирована скидка 10% за 2 подписки'),
    'discount_15' => site_lang('lk_subscriptions|discount_hint_15', 'Активирована скидка 15% за 3+ подписки'),
    'discount_20' => site_lang('lk_subscriptions|discount_hint_20', 'Активирована скидка 20% за все подписки'),
  ];
@endphp
<script>
(function () {
  "use strict";

  // Данные из сервера
  const SUBSCRIPTIONS = @json($subscriptionsData);
  
  const TARIFFS = @json($tariffsData);
  const UI_TEXTS = @json($uiTexts);

  // ---------- Утилиты ----------
  function formatRUB(value) {
    const n = Math.round(value);
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " ₽";
  }

  function $(sel) {
    return document.querySelector(sel);
  }

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
    tariffId: null,
  };

  function loadState() {
    try {
      const raw = localStorage.getItem(LS_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed.selectedSubs)) {
        state.selectedSubs = new Set(parsed.selectedSubs);
      }
      if (typeof parsed.tariffId === "string" || typeof parsed.tariffId === "number") {
        state.tariffId = parsed.tariffId;
      }
    } catch (e) {
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

  // ---------- Инициализация состояния из DOM ----------
  function initState() {
    // Восстанавливаем выбранные подписки
    document.querySelectorAll('.sub-checkbox').forEach(cb => {
      const subId = cb.closest('.sub-option').getAttribute('data-sub-id');
      if (state.selectedSubs.has(subId)) {
        cb.checked = true;
      }
    });

    // Восстанавливаем выбранный тариф
    if (state.tariffId) {
      const radio = document.querySelector(`input[name="tariff"][value="${state.tariffId}"]`);
      if (radio) {
        radio.checked = true;
        state.tariffId = parseInt(radio.value);
      }
    } else {
      // Выбираем первый тариф по умолчанию
      const firstRadio = document.querySelector('input[name="tariff"]');
      if (firstRadio) {
        firstRadio.checked = true;
        state.tariffId = parseInt(firstRadio.value);
      }
    }
  }

  // ---------- Обработчики событий ----------
  function bindEvents() {
    // Обработчики для чекбоксов подписок
    document.querySelectorAll('.sub-checkbox').forEach(cb => {
      cb.addEventListener('change', () => {
        const subId = cb.closest('.sub-option').getAttribute('data-sub-id');
        if (cb.checked) {
          state.selectedSubs.add(subId);
        } else {
          state.selectedSubs.delete(subId);
        }
        saveState();
        recalc();
      });
    });

    // Обработчики для радиокнопок тарифов
    document.querySelectorAll('.tariff-radio').forEach(radio => {
      radio.addEventListener('change', () => {
        if (radio.checked) {
          state.tariffId = parseInt(radio.value);
          saveState();
          recalc();
        }
      });
    });
  }

  // ---------- Расчёт ----------
  function recalc() {
    const count = state.selectedSubs.size;
    const tariff = TARIFFS.find((t) => t.id == state.tariffId) || null;

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
      if (count <= 1) hint.textContent = UI_TEXTS.discount_none;
      else if (discountPercent === 10) hint.textContent = UI_TEXTS.discount_10;
      else if (discountPercent === 15) hint.textContent = UI_TEXTS.discount_15;
      else if (discountPercent === 20) hint.textContent = UI_TEXTS.discount_20;
      else hint.textContent = "";
    }

    // Подготовка "payload" заказа
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
    };

    // Debug блок (скрыт)
    const dbg = $("#debugOrder");
    if (dbg) dbg.textContent = JSON.stringify(orderPayload, null, 2);

    // Сохраним последний payload
    try {
      localStorage.setItem("sovushka_last_order_payload", JSON.stringify(orderPayload));
    } catch (e) {}
  }

  // ---------- Оплата (заглушка) ----------
  function bindPayButton() {
    const btn = $("#payBtn");
    if (!btn) return;

    btn.addEventListener("click", () => {
      const last = localStorage.getItem("sovushka_last_order_payload");
      console.log("Checkout payload (demo):", last);
      alert("Демо: заказ сформирован. Подключение оплаты будет добавлено позже.");
    });
  }

  // ---------- Инициализация ----------
  function init() {
    if (!document.getElementById("subscriptionsApp")) return;

    loadState();
    initState();
    bindEvents();
    bindPayButton();
    recalc();
  }

  // DOMContentLoaded нам не нужен, потому что script подключён с defer
  init();
})();
</script>
@endpush
