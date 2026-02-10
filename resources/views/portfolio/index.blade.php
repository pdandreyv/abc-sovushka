@extends('layouts.app')

@section('title', site_lang('lk_portfolio|page_title', 'Портфолио — Совушкина школа'))

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => 'portfolio'])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => site_lang('lk_portfolio|crumb_home', 'Главная'), 'url' => url('/')],
      ['label' => site_lang('lk_portfolio|crumb_cabinet', 'Кабинет'), 'url' => route('dashboard')],
      ['label' => site_lang('lk_portfolio|crumb_portfolio', 'Портфолио'), 'url' => null],
    ],
  ])
  <div class="content">
    <h1>{{ site_lang('lk_portfolio|heading', 'Портфолио') }}</h1>
    <p class="page-hint">
      {{ site_lang('lk_portfolio|hint', 'Здесь собраны ваши сертификаты, дипломы и другие награды. Нажмите «Посмотреть», чтобы открыть документ в новом окне почти на весь экран, или «Скачать», чтобы открыть файл для скачивания в новом окне (текущая страница останется открытой).') }}
    </p>

    <div class="cards portfolio-grid">
      @forelse($items as $item)
        <div class="card award-card">
          <div class="award-thumb">
            <span class="award-badge badge-certificate">{{ $item->badge }}</span>
            @if($item->image_thumb)
              <img alt="{{ $item->title }}" src="{{ asset('files/portfolio_items/'.$item->id.'/image_thumb/'.$item->image_thumb) }}"/>
            @else
              <img alt="{{ $item->title }}" src="{{ asset('images/placeholder.jpg') }}"/>
            @endif
          </div>
          <div class="award-actions">
            @if($item->image_file)
              @php
                $fileUrl = asset('files/portfolio_items/'.$item->id.'/image_file/'.$item->image_file);
              @endphp
              <button class="btn btn-secondary" type="button" data-view-doc="{{ $fileUrl }}">{{ site_lang('lk_portfolio|view', 'Посмотреть') }}</button>
              <a class="btn btn-primary" href="{{ $fileUrl }}" target="_blank" rel="noopener">{{ site_lang('lk_portfolio|download', 'Скачать') }}</a>
            @endif
          </div>
          <div class="award-title">{{ $item->title }}</div>
        </div>
      @empty
        <div class="card" style="grid-column: 1 / -1;">
          <p>{{ site_lang('lk_portfolio|empty', 'Пока нет добавленных наград.') }}</p>
        </div>
      @endforelse
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset_versioned('js/dashboard.js') }}"></script>
@endpush
