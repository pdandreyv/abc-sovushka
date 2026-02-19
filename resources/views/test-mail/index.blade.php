@extends('layouts.app')

@section('title', 'Тестовое письмо — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/dashboard.css') }}">
@endpush

@section('content')
@include('partials.sidebar', ['sidebarActive' => 'dashboard'])

<div class="main">
  @include('partials.lk-header', [
    'breadcrumbItems' => [
      ['label' => 'Главная', 'url' => route('dashboard')],
      ['label' => 'Тестовое письмо', 'url' => null],
    ],
  ])
  <div class="content">
    <h1>Отправка тестового письма</h1>
    <p class="page-hint" style="margin-bottom: 24px;">
      Проверка настройки почты на сервере. Укажите email получателя и нажмите «Отправить». Доступ только для администраторов.
    </p>

    @if (session('success'))
      <div class="toast toast-success show" style="max-width: 520px; margin-bottom: 20px;">
        ✅ {{ session('success') }}
      </div>
    @endif
    @if (session('error'))
      <div class="toast toast-error show" style="max-width: 520px; margin-bottom: 20px;">
        ❌ {{ session('error') }}
      </div>
    @endif

    <div class="card" style="max-width: 520px;">
      <div class="card-header">
        <h3>Тестовое письмо</h3>
      </div>
      <form method="POST" action="{{ route('test-mail.send') }}">
        @csrf
        <div style="margin-bottom: 16px;">
          <label for="email" style="display: block; margin-bottom: 6px; font-weight: 600;">Email получателя</label>
          <input type="email" name="email" id="email" value="{{ old('email') }}" required
                 placeholder="example@mail.ru" class="@error('email') is-invalid @enderror"
                 style="width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px;">
          @error('email')
            <span class="invalid-feedback" style="color: #c00; font-size: 14px;">{{ $message }}</span>
          @enderror
        </div>
        <button type="submit" class="btn btn-primary">Отправить</button>
      </form>
    </div>

    <p style="margin-top: 20px; color: #666; font-size: 14px;">
      Текущий драйвер почты: <strong>{{ config('mail.default') }}</strong>.
      Отправитель: <strong>{{ config('mail.from.address') }}</strong> ({{ config('mail.from.name') }}).
    </p>
  </div>
</div>
@endsection
