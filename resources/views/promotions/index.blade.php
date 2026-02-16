@extends('layouts.app')

@section('title', site_lang('lk_promotion|page_title', 'Акция — Совушкина школа'))

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
<style>
  .promo-step-card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 20px;
    max-width: 600px;
  }
  .promo-step-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 16px;
  }
  .promo-subs-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .promo-sub-item {
    display: grid;
    grid-template-columns: 1fr minmax(200px, auto);
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px dashed #eee;
  }
  .promo-sub-item:last-child { border-bottom: none; }
  .promo-sub-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 13px;
    color: #5a5a5a;
  }
  .promo-attach-block {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e8e8e8;
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
  }
</style>
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => 'promotion'])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => site_lang('lk_subscriptions|crumb_home', 'Главная'), 'url' => url('/')],
      ['label' => site_lang('lk_promotion|crumb_promotion', 'Акция'), 'url' => null],
    ],
  ])
  <div class="content">
    <h1>{{ site_lang('lk_promotion|heading', 'Акция') }}</h1>
    @if (session('success'))
      <div id="toast-success" class="toast-success" role="alert">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="promo-step-card">
      <div class="promo-step-title">{{ site_lang('lk_promotion|selected_subscriptions', 'Выбранные подписки') }}</div>
      <div class="promo-subs-list">
        @foreach($levels as $level)
        <div class="promo-sub-item">
          <span class="promo-sub-title">{{ $level->title }}</span>
          <div class="promo-sub-meta">
            <div>{{ site_lang('lk_subscriptions|active_till', 'Оплачено до:') }} {{ $date_till->format('d.m.Y') }}</div>
            @if($tariff)
              <div>{{ site_lang('lk_subscriptions|tariff_label', 'Тариф:') }} {{ $tariff->title }} ({{ number_format((float) $promotion->special_price, 0, ',', ' ') }} {{ site_lang('lk_subscriptions|rubles', '₽') }})</div>
            @endif
            <div>{{ site_lang('lk_subscriptions|next_charge', 'Следующее списание:') }} {{ $next_charge_date->format('d.m.Y') }}</div>
          </div>
        </div>
        @endforeach
      </div>

      <div class="promo-attach-block">
        <form method="POST" action="{{ route('promotion.attach-card') }}">
          @csrf
          <button type="submit" class="btn btn-primary">{{ site_lang('lk_promotion|attach_card', 'Привязать карту') }}</button>
        </form>
        <p class="text-muted mt-2 mb-0" style="font-size: 14px;">
          {{ site_lang('lk_promotion|attach_hint', 'Привязка карты бесплатна. Вы получите бесплатный период, затем списание по специальной цене.') }}
        </p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
@endpush
