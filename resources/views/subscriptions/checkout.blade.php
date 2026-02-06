@extends('layouts.app')

@section('title', site_lang('lk_subscriptions|checkout_title', 'Оплата подписки — Совушкина школа'))

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
<style>
  .checkout-card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 16px;
    padding: 24px;
    max-width: 720px;
  }
  .checkout-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 8px 0;
    border-bottom: 1px dashed #ececec;
  }
  .checkout-row:last-child {
    border-bottom: none;
  }
  .checkout-levels {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .checkout-level {
    background: #f3f6ff;
    color: #203a8f;
    padding: 6px 10px;
    border-radius: 10px;
    font-size: 14px;
  }
  .checkout-actions {
    display: flex;
    gap: 12px;
    margin-top: 16px;
  }
  .checkout-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px 16px;
    margin-top: 16px;
  }
  .checkout-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .checkout-field label {
    font-size: 13px;
    color: #4a4a4a;
  }
  .checkout-field input {
    border: 1px solid #d7d7d7;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 14px;
  }
  .checkout-field.full {
    grid-column: 1 / -1;
  }
  .checkout-note {
    margin-top: 12px;
    color: #6a6a6a;
    font-size: 14px;
  }
  .checkout-errors {
    padding: 12px 16px;
    margin-bottom: 16px;
    background: #ffebee;
    border: 1px solid #ef5350;
    border-radius: 10px;
    color: #c62828;
    font-size: 13px;
  }
  .checkout-errors ul {
    margin: 0;
    padding-left: 18px;
  }
  .checkout-errors li {
    margin: 4px 0;
  }
</style>
@endpush

@section('content')
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
      <button type="button" data-href="{{ route('profile.show') }}">{{ site_lang('lk_menu|profile', 'Личные данные') }}</button>
      <button type="button" data-href="{{ route('dashboard') }}">{{ site_lang('lk_menu|portfolio', 'Портфолио') }}</button>
      <button class="active" type="button" data-href="{{ route('subscriptions.index') }}">{{ site_lang('lk_menu|subscriptions', 'Подписки') }}</button>
      <button type="button" data-href="{{ route('ideas.index') }}">{{ site_lang('lk_menu|ideas', 'Кладовая идей') }}</button>
    </div>
  </div>
</div>

<div class="main">
  <div class="header">
    <div class="breadcrumbs">{{ site_lang('lk_subscriptions|breadcrumbs_checkout', 'Главная / Кабинет / Подписки / Оплата') }}</div>
    <div class="header-icons">
      <img alt="Подписка" src="{{ asset('images/subscription_icon.png') }}"/>
      <span class="subscription-status">{{ site_lang('lk_subscriptions|status_checkout', 'Оплата') }}</span>
    </div>
  </div>

  <div class="content">
    <div class="checkout-card">
      <h2 style="margin-top: 0;">{{ site_lang('lk_subscriptions|checkout_header', 'Оплата подписки') }}</h2>

      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="checkout-errors" role="alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="checkout-row">
        <div>{{ site_lang('lk_subscriptions|checkout_levels', 'Выбранные подписки') }}</div>
        <div class="checkout-levels">
          @foreach ($levels as $level)
            <span class="checkout-level">{{ $level->title }}</span>
          @endforeach
        </div>
      </div>
      <div class="checkout-row">
        <div>{{ site_lang('lk_subscriptions|checkout_tariff', 'Тариф') }}</div>
        <div>{{ $tariff ? $tariff->title : '—' }}</div>
      </div>
      <div class="checkout-row">
        <div>{{ site_lang('lk_subscriptions|checkout_days', 'Дней в тарифе') }}</div>
        <div>{{ $order->days }}</div>
      </div>
      <div class="checkout-row">
        <div>{{ site_lang('lk_subscriptions|checkout_sum', 'К оплате') }}</div>
        <div><b>{{ number_format((float) $order->sum_subscription, 0, '.', ' ') }} ₽</b></div>
      </div>

      @if ($order->paid)
        <div class="checkout-actions">
          <span class="btn btn-primary" disabled>{{ site_lang('lk_subscriptions|checkout_paid', 'Оплачено') }}</span>
          <a class="btn btn-secondary" href="{{ route('subscriptions.index') }}">{{ site_lang('lk_subscriptions|checkout_back', 'Вернуться к выбору') }}</a>
        </div>
      @elseif (!empty($useYookassa))
        <form method="POST" action="{{ route('subscriptions.yookassa.redirect') }}">
          @csrf
          <input type="hidden" name="order_id" value="{{ $order->id }}">
          <div class="checkout-actions">
            <button class="btn btn-primary" type="submit">{{ site_lang('lk_subscriptions|checkout_pay', 'Оплатить') }} (ЮKassa)</button>
            <a class="btn btn-secondary" href="{{ route('subscriptions.index') }}">{{ site_lang('lk_subscriptions|checkout_back', 'Вернуться к выбору') }}</a>
          </div>
        </form>
        <div class="checkout-note">
          Вы будете перенаправлены на защищённую страницу оплаты ЮKassa. Карта сохранится для автопродления подписки.
        </div>
      @else
        <form id="checkout-form" method="POST" action="{{ route('subscriptions.checkout.confirm') }}">
          @csrf
          <input type="hidden" name="order_id" value="{{ $order->id }}">
          <div class="checkout-form">
            <div class="checkout-field full">
              <label for="card_number">Номер карты</label>
              <input
                id="card_number"
                name="card_number"
                type="text"
                inputmode="numeric"
                autocomplete="cc-number"
                placeholder="0000 0000 0000 0000"
                maxlength="19"
                value="{{ old('card_number') }}"
                required
              >
            </div>
            <div class="checkout-field">
              <label for="card_exp">Срок действия</label>
              <input
                id="card_exp"
                name="card_exp"
                type="text"
                inputmode="numeric"
                autocomplete="cc-exp"
                placeholder="MM/YY"
                maxlength="5"
                value="{{ old('card_exp') }}"
                required
              >
            </div>
            <div class="checkout-field">
              <label for="card_cvc">CVC/CVV</label>
              <input
                id="card_cvc"
                name="card_cvc"
                type="password"
                inputmode="numeric"
                autocomplete="cc-csc"
                placeholder="***"
                required
              >
            </div>
            <div class="checkout-field">
              <label for="card_holder">Имя держателя</label>
              <input
                id="card_holder"
                name="card_holder"
                type="text"
                autocomplete="cc-name"
                placeholder="IVAN IVANOV"
                value="{{ old('card_holder') }}"
                required
              >
            </div>
            <div class="checkout-field full">
              <label for="payer_email">Email для чека</label>
              <input
                id="payer_email"
                name="payer_email"
                type="email"
                autocomplete="email"
                placeholder="mail@example.com"
                value="{{ old('payer_email', Auth::user()->email) }}"
                required
              >
            </div>
          </div>
          <div class="checkout-actions">
            <button class="btn btn-primary" type="submit">{{ site_lang('lk_subscriptions|checkout_pay', 'Оплатить') }}</button>
            <a class="btn btn-secondary" href="{{ route('subscriptions.index') }}">{{ site_lang('lk_subscriptions|checkout_back', 'Вернуться к выбору') }}</a>
          </div>
        </form>
        <div class="checkout-note">
          {{ site_lang('lk_subscriptions|checkout_test_note', 'Тестовый режим: данные карты используются для имитации YooKassa. Настройте YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY для реальной оплаты.') }}
        </div>
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script>
  (function () {
    "use strict";
    document.querySelectorAll("[data-href]").forEach((btn) => {
      btn.addEventListener("click", () => {
        window.location.href = btn.getAttribute("data-href");
      });
    });

    const form = document.getElementById("checkout-form");
    const cardNumber = document.getElementById("card_number");
    const cardExp = document.getElementById("card_exp");
    const cardCvc = document.getElementById("card_cvc");
    const cardHolder = document.getElementById("card_holder");
    const payerEmail = document.getElementById("payer_email");

    function onlyDigits(value) {
      return value.replace(/\D+/g, "");
    }

    function formatCardNumber(value) {
      const digits = onlyDigits(value).slice(0, 16);
      return digits.replace(/(\d{4})(?=\d)/g, "$1 ").trim();
    }

    function formatCardExp(value) {
      const digits = onlyDigits(value).slice(0, 4);
      if (digits.length <= 2) return digits;
      return `${digits.slice(0, 2)}/${digits.slice(2)}`;
    }

    function isValidExp(value) {
      const match = value.match(/^(\d{2})\/(\d{2})$/);
      if (!match) return false;
      const month = parseInt(match[1], 10);
      const year = parseInt(match[2], 10);
      return month >= 1 && month <= 12 && year >= 0 && year <= 99;
    }

    function showError(input, message) {
      input.setCustomValidity(message);
    }

    function clearError(input) {
      input.setCustomValidity("");
    }

    if (cardNumber) {
      cardNumber.addEventListener("input", () => {
        const formatted = formatCardNumber(cardNumber.value);
        cardNumber.value = formatted;
        const digits = onlyDigits(formatted);
        if (digits.length !== 16) {
          showError(cardNumber, "Номер карты должен содержать 16 цифр.");
        } else {
          clearError(cardNumber);
        }
      });
    }

    if (cardExp) {
      cardExp.addEventListener("input", () => {
        const formatted = formatCardExp(cardExp.value);
        cardExp.value = formatted;
        if (!isValidExp(formatted)) {
          showError(cardExp, "Введите срок в формате MM/YY.");
        } else {
          clearError(cardExp);
        }
      });
    }

    if (cardCvc) {
      cardCvc.addEventListener("input", () => {
        cardCvc.value = onlyDigits(cardCvc.value).slice(0, 4);
        if (cardCvc.value.length < 3) {
          showError(cardCvc, "CVC должен содержать 3–4 цифры.");
        } else {
          clearError(cardCvc);
        }
      });
    }

    if (form) {
      form.addEventListener("submit", (event) => {
        let isValid = true;

        if (cardNumber) {
          const digits = onlyDigits(cardNumber.value);
          if (digits.length !== 16) {
            showError(cardNumber, "Номер карты должен содержать 16 цифр.");
            isValid = false;
          }
        }

        if (cardExp && !isValidExp(cardExp.value)) {
          showError(cardExp, "Введите срок в формате MM/YY.");
          isValid = false;
        }

        if (cardCvc && cardCvc.value.length < 3) {
          showError(cardCvc, "CVC должен содержать 3–4 цифры.");
          isValid = false;
        }

        if (cardHolder && cardHolder.value.trim().length < 2) {
          showError(cardHolder, "Укажите имя держателя.");
          isValid = false;
        } else if (cardHolder) {
          clearError(cardHolder);
        }

        if (payerEmail && !payerEmail.checkValidity()) {
          showError(payerEmail, "Укажите корректный email.");
          isValid = false;
        } else if (payerEmail) {
          clearError(payerEmail);
        }

        if (!isValid) {
          event.preventDefault();
          form.reportValidity();
        }
      });
    }
  })();
</script>
@endpush
@endsection
