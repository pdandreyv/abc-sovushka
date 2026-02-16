@extends('layouts.app')

@section('title', site_lang('lk_subscriptions|page_title', 'Подписки — Совушкина школа'))

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
<style>
  .sub-option.sub-option--meta {
    display: grid;
    grid-template-columns: 1fr minmax(220px, auto);
    align-items: start;
    gap: 12px;
  }
  .sub-left-col {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .sub-details {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-end;
    min-width: 0;
  }
  .sub-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px;
    color: #5a5a5a;
    align-items: flex-end;
  }
  .sub-meta-actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .sub-card-info {
    font-size: 13px;
    color: #555;
  }
  .sub-meta-actions form {
    flex-wrap: wrap;
    justify-content: flex-end;
  }
  .sub-meta-actions .btn {
    padding: 6px 10px;
    font-size: 12px;
  }
  .sub-recurring-link {
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    cursor: pointer;
    text-decoration: underline;
    color: inherit;
    white-space: nowrap;
  }
  .sub-recurring-link--cancel {
    color: #8b2942;
  }
  .sub-recurring-link--cancel:hover {
    color: #6b1f33;
  }
  .sub-recurring-link--enable {
    color: #2e7d32;
  }
  .sub-recurring-link--enable:hover {
    color: #1b5e20;
  }
  .sub-actions {
    display: flex;
    align-items: center;
  }
  .toast-success {
    position: fixed;
    top: 24px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    padding: 14px 24px;
    background: #e8f5e9;
    border: 1px solid #4caf50;
    border-radius: 12px;
    color: #2e7d32;
    font-size: 15px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.25);
    animation: toast-in 0.3s ease;
  }
  @keyframes toast-in {
    from { opacity: 0; transform: translateX(-50%) translateY(-12px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
  }
  .toast-success.toast-out {
    animation: toast-out 0.3s ease forwards;
  }
  @keyframes toast-out {
    from { opacity: 1; transform: translateX(-50%) translateY(0); }
    to { opacity: 0; transform: translateX(-50%) translateY(-12px); }
  }
  .promo-box { margin-top: 1rem; }
  .promo-label { display: block; font-weight: 500; margin-bottom: 6px; }
  .promo-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
  .promo-input { flex: 1; min-width: 140px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; }
  .promo-btn { white-space: nowrap; }
  .promo-message { margin-top: 8px; font-size: 14px; min-height: 1.4em; }
  .promo-message.success { color: #2e7d32; }
  .promo-message.error { color: #c62828; }
</style>
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => 'subscriptions'])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => site_lang('lk_subscriptions|crumb_home', 'Главная'), 'url' => url('/')],
      ['label' => site_lang('lk_subscriptions|crumb_subscriptions', 'Подписки'), 'url' => null],
    ],
  ])
  <div class="content">
    <h1>{{ site_lang('lk_subscriptions|heading', 'Подписки') }}</h1>
    @if (session('success'))
      <div id="toast-success" class="toast-success" role="alert">{{ session('success') }}</div>
    @endif
    <p class="page-hint">
      {{ site_lang('lk_subscriptions|hint', 'Выберите нужные подписки и тариф — итоговая сумма и скидка пересчитаются автоматически.') }}
    </p>

    @if(!empty($recurringMultiLevel))
    <div class="step-card recurring-multi-block" style="margin-bottom: 20px;">
      <div class="step-title">{{ site_lang('lk_promotion|recurring_multi_title', 'Акционная подписка') }}</div>
      @foreach($recurringMultiLevel as $rec)
      <div class="sub-option sub-option--meta">
        <div class="sub-left-col">
          <span class="sub-title">{{ implode(', ', $rec['level_titles']) }}</span>
        </div>
        <div class="sub-details">
        <div class="sub-meta">
          <div>{{ site_lang('lk_subscriptions|next_charge', 'Следующее списание:') }} {{ \Illuminate\Support\Carbon::parse($rec['date_next_pay'])->format('d.m.Y') }}</div>
          <div>{{ site_lang('lk_subscriptions|summary_total', 'Итого:') }} {{ number_format($rec['sum_next_pay'], 0, ',', ' ') }} {{ site_lang('lk_subscriptions|rubles', '₽') }}</div>
          @if(!empty($rec['card_last4']))
          <div class="sub-card-info">{{ site_lang('lk_subscriptions|card_number', 'Карта') }} **** {{ $rec['card_last4'] }}</div>
          @endif
          <div class="sub-meta-actions">
            <form class="js-recurring-toggle-form" method="POST" action="{{ route('subscriptions.recurring.toggle', ['level' => $rec['first_level_id']]) }}" data-confirm-cancel="{{ site_lang('lk_subscriptions|confirm_cancel_autorenew', 'Вы уверены, что хотите отменить автопродление?') }}" data-confirm-enable="{{ site_lang('lk_subscriptions|confirm_enable_autorenew', 'Включить автопродление подписки?') }}">
              @csrf
              <input type="hidden" name="enable" value="{{ $rec['auto'] ? 0 : 1 }}">
              <button class="sub-recurring-link sub-recurring-link--{{ $rec['auto'] ? 'cancel' : 'enable' }}" type="submit">
                {{ $rec['auto'] ? site_lang('lk_subscriptions|cancel_autorenew', 'Отменить автопродление') : site_lang('lk_subscriptions|enable_autorenew', 'Включить автопродление') }}
              </button>
            </form>
          </div>
        </div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

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
          @php
            $levelLink = $level->link;
            $isDemoLink = $levelLink && (str_ends_with($levelLink, '.html') || str_contains($levelLink, 'demo/sub_'));
            $isAbsolute = $levelLink && (str_starts_with($levelLink, 'http://') || str_starts_with($levelLink, 'https://') || str_starts_with($levelLink, '/'));

            if (!$levelLink || $isDemoLink) {
              $levelLink = route('subjects.index', ['level' => $level->id]);
            } elseif (!$isAbsolute) {
              $levelLink = route('subjects.index', ['level' => $levelLink]);
            }
          @endphp
          <div class="sub-option sub-option--meta" data-sub-id="{{ $level->id }}">
            <div class="sub-left-col">
              <label class="sub-left" for="sub_{{ $level->id }}">
                <input
                  class="sub-checkbox"
                  id="sub_{{ $level->id }}"
                  type="checkbox"
                  value="{{ $level->id }}"
                  {{ isset($activeByLevel[$level->id]) ? 'disabled' : '' }}
                />
                <span class="sub-title">{{ $level->title }}</span>
              </label>
              @if($levelLink)
                <div class="sub-actions">
                  <a class="btn btn-secondary btn-sm" href="{{ $levelLink }}">{{ site_lang('lk_subscriptions|view', 'Посмотреть') }}</a>
                </div>
              @endif
            </div>
            @php
              $activeInfo = $activeByLevel[$level->id] ?? null;
              $recurringInfo = $recurringByLevel[$level->id] ?? null;
              $activeTariff = $activeInfo && !empty($activeInfo['tariff_id']) ? $tariffs->firstWhere('id', $activeInfo['tariff_id']) : null;
            @endphp
            <div class="sub-details">
              @if($activeInfo)
                <div class="sub-meta">
                  <div>
                    {{ site_lang('lk_subscriptions|active_till', 'Оплачено до:') }}
                    {{ \Illuminate\Support\Carbon::parse($activeInfo['date_till'])->format('d.m.Y') }}
                  </div>
                  @if($activeTariff)
                    <div>
                      {{ site_lang('lk_subscriptions|tariff_label', 'Тариф:') }}
                      {{ $activeTariff->title }} ({{ number_format((float) $activeTariff->price, 0, ',', ' ') }} {{ site_lang('lk_subscriptions|rubles', '₽') }})
                    </div>
                  @endif
                    @if($recurringInfo)
                    @if($recurringInfo['auto'])
                      <div>
                        {{ site_lang('lk_subscriptions|next_charge', 'Следующее списание:') }}
                        {{ \Illuminate\Support\Carbon::parse($recurringInfo['date_next_pay'])->format('d.m.Y') }}
                      </div>
                    @endif
                    <div class="sub-meta-actions">
                      @if(!empty($recurringInfo['card_last4']))
                        <span class="sub-card-info">{{ site_lang('lk_subscriptions|card_number', 'Карта') }} **** {{ $recurringInfo['card_last4'] }}</span>
                      @endif
                      <form class="js-recurring-toggle-form" method="POST" action="{{ route('subscriptions.recurring.toggle', ['level' => $level->id]) }}" data-confirm-cancel="{{ site_lang('lk_subscriptions|confirm_cancel_autorenew', 'Вы уверены, что хотите отменить автопродление?') }}" data-confirm-enable="{{ site_lang('lk_subscriptions|confirm_enable_autorenew', 'Включить автопродление подписки?') }}">
                        @csrf
                        <input type="hidden" name="enable" value="{{ $recurringInfo['auto'] ? 0 : 1 }}">
                        <button class="sub-recurring-link sub-recurring-link--{{ $recurringInfo['auto'] ? 'cancel' : 'enable' }}" type="submit">
                          {{ $recurringInfo['auto']
                            ? site_lang('lk_subscriptions|cancel_autorenew', 'Отменить автопродление')
                            : site_lang('lk_subscriptions|enable_autorenew', 'Включить автопродление') }}
                        </button>
                      </form>
                    </div>
                  @endif
                </div>
              @endif
            </div>
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

        <!-- Промокод -->
        <div class="promo-box">
          <label for="promoInput" class="promo-label">{{ site_lang('lk_subscriptions|promo_label', 'Промокод') }}</label>
          <div class="promo-row">
            <input type="text" id="promoInput" class="promo-input" placeholder="{{ site_lang('lk_subscriptions|promo_placeholder', 'Введите код') }}" maxlength="64" autocomplete="off"/>
            <button type="button" id="promoApplyBtn" class="btn btn-secondary promo-btn">{{ site_lang('lk_subscriptions|promo_apply', 'Применить') }}</button>
          </div>
          <div id="promoMessage" class="promo-message" aria-live="polite"></div>
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
  const CHECKOUT_URL = @json(route('subscriptions.checkout.create'));
  const APPLY_CODE_URL = @json(route('subscriptions.apply-code'));
  const CSRF_TOKEN = @json(csrf_token());
  const ACTIVE_COUNT = @json(count($activeByLevel ?? []));

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
  function getDiscountPercent(totalCount) {
    if (totalCount >= SUBSCRIPTIONS.length) return 20;
    if (totalCount >= 3) return 15;
    if (totalCount === 2) return 10;
    return 0;
  }

  // ---------- Состояние ----------
  const LS_KEY = "sovushka_subscriptions_state_v1";
  const state = {
    selectedSubs: new Set(),
    tariffId: null,
    appliedPromo: null,
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
      if (cb.disabled) {
        cb.checked = false;
        state.selectedSubs.delete(subId);
        return;
      }
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
        if (cb.disabled) return;
        const subId = cb.closest('.sub-option').getAttribute('data-sub-id');
        if (cb.checked) {
          state.selectedSubs.add(subId);
        } else {
          state.selectedSubs.delete(subId);
        }
        clearPromo();
        saveState();
        recalc();
      });
    });

    // Обработчики для радиокнопок тарифов
    document.querySelectorAll('.tariff-radio').forEach(radio => {
      radio.addEventListener('change', () => {
        if (radio.checked) {
          state.tariffId = parseInt(radio.value);
          clearPromo();
          saveState();
          recalc();
        }
      });
    });
  }

  function clearPromo() {
    state.appliedPromo = null;
    const msg = $("#promoMessage");
    if (msg) { msg.textContent = ""; msg.className = "promo-message"; }
    const inp = $("#promoInput");
    if (inp) inp.value = "";
  }

  // ---------- Расчёт ----------
  function recalc() {
    const selectedIds = Array.from(
      document.querySelectorAll('.sub-checkbox:checked:not(:disabled)')
    ).map(cb => cb.closest('.sub-option').getAttribute('data-sub-id'));
    const count = selectedIds.length;
    state.selectedSubs = new Set(selectedIds);
    const tariff = TARIFFS.find((t) => t.id == state.tariffId) || null;

    const pricePerSub = tariff ? tariff.price : 0;
    const subtotal = count * pricePerSub;

    let discountPercent = getDiscountPercent(count + ACTIVE_COUNT);
    if (state.appliedPromo != null) discountPercent = state.appliedPromo.discount_percent;
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
      if (state.appliedPromo != null) hint.textContent = state.appliedPromo.message || "";
      else if (count <= 1) hint.textContent = UI_TEXTS.discount_none;
      else if (discountPercent === 10) hint.textContent = UI_TEXTS.discount_10;
      else if (discountPercent === 15) hint.textContent = UI_TEXTS.discount_15;
      else if (discountPercent === 20) hint.textContent = UI_TEXTS.discount_20;
      else hint.textContent = "";
    }

    // Подготовка "payload" заказа
    const orderPayload = {
      subscriptions: selectedIds,
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
      const selectedIds = Array.from(
        document.querySelectorAll('.sub-checkbox:checked:not(:disabled)')
      ).map(cb => cb.closest('.sub-option').getAttribute('data-sub-id'));
      if (!state.tariffId || selectedIds.length === 0) return;

      const form = document.createElement("form");
      form.method = "POST";
      form.action = CHECKOUT_URL;

      const csrf = document.createElement("input");
      csrf.type = "hidden";
      csrf.name = "_token";
      csrf.value = CSRF_TOKEN;
      form.appendChild(csrf);

      const tariff = document.createElement("input");
      tariff.type = "hidden";
      tariff.name = "tariff_id";
      tariff.value = String(state.tariffId);
      form.appendChild(tariff);

      selectedIds.forEach((id) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "levels[]";
        input.value = String(id);
        form.appendChild(input);
      });

      if (state.appliedPromo && state.appliedPromo.code) {
        const codeInput = document.createElement("input");
        codeInput.type = "hidden";
        codeInput.name = "discount_code";
        codeInput.value = state.appliedPromo.code;
        form.appendChild(codeInput);
      }

      document.body.appendChild(form);
      form.submit();
    });
  }

  function bindPromoButton() {
    const btn = $("#promoApplyBtn");
    const input = $("#promoInput");
    const msgEl = $("#promoMessage");
    if (!btn || !input || !msgEl) return;

    btn.addEventListener("click", function () {
      const code = input.value.trim();
      const selectedIds = Array.from(
        document.querySelectorAll(".sub-checkbox:checked:not(:disabled)")
      ).map((cb) => cb.closest(".sub-option").getAttribute("data-sub-id"));

      msgEl.textContent = "";
      msgEl.className = "promo-message";

      if (!code) {
        msgEl.className = "promo-message error";
        msgEl.textContent = "Введите код.";
        return;
      }
      if (selectedIds.length === 0) {
        msgEl.className = "promo-message error";
        msgEl.textContent = "Сначала выберите подписки.";
        return;
      }

      btn.disabled = true;
      fetch(APPLY_CODE_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": CSRF_TOKEN,
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ code: code, level_ids: selectedIds }),
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            state.appliedPromo = {
              code: code,
              discount_percent: data.discount_percent,
              level_titles: data.level_titles || [],
              message: data.message || "",
            };
            msgEl.className = "promo-message success";
            msgEl.textContent = data.message || "Скидка применена.";
            recalc();
          } else {
            let text = data.message || "Код недействителен.";
            if (data.error === "code_no_match" && (data.level_titles || []).length > 0) {
              text += " Код действует на подписки: " + (data.level_titles || []).join(", ") + ".";
            }
            msgEl.className = "promo-message error";
            msgEl.textContent = text;
          }
        })
        .catch(() => {
          msgEl.className = "promo-message error";
          msgEl.textContent = "Ошибка связи. Попробуйте позже.";
        })
        .finally(() => {
          btn.disabled = false;
        });
    });
  }

  // Подтверждение перед отменой/включением автопродления
  document.addEventListener("submit", function (e) {
    const form = e.target;
    if (!form || !form.classList.contains("js-recurring-toggle-form")) return;
    e.preventDefault();
    const enableInput = form.querySelector('input[name="enable"]');
    const enable = enableInput ? enableInput.value : "";
    const msg = enable === "1"
      ? (form.dataset.confirmEnable || "Включить автопродление подписки?")
      : (form.dataset.confirmCancel || "Вы уверены, что хотите отменить автопродление?");
    if (confirm(msg)) {
      form.submit();
    }
  });

  // ---------- Инициализация ----------
  function init() {
    if (!document.getElementById("subscriptionsApp")) return;

    loadState();
    initState();
    bindEvents();
    bindPromoButton();
    bindPayButton();
    recalc();
  }

  // Попап успеха — скрыть через 2.5 сек
  (function () {
    const toast = document.getElementById("toast-success");
    if (toast) {
      setTimeout(function () {
        toast.classList.add("toast-out");
        setTimeout(function () {
          toast.remove();
        }, 300);
      }, 2500);
    }
  })();

  // DOMContentLoaded нам не нужен, потому что script подключён с defer
  init();
})();
</script>
@endpush
