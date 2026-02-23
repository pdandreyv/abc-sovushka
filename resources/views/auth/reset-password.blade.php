@extends('layouts.app')

@section('title', 'Новый пароль — Совушкина школа')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/auth.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="left">
        <img src="{{ asset('images/logo.png') }}" alt="Логотип" />
        <div class="welcome-text">
            <h3>{{ site_lang('auth|welcome_title', 'Добро пожаловать в Совушкину школу!') }}</h3>
            <p>Задайте новый пароль для входа в личный кабинет. Старый пароль вводить не нужно.</p>
        </div>
    </div>
    <div class="right">
        <div class="form-box">
            <h2>Новый пароль</h2>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="password" name="password" placeholder="Новый пароль" required minlength="8" autofocus class="@error('password') input-error @enderror" />
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
                <input type="password" name="password_confirmation" placeholder="Повторите новый пароль" required minlength="8" />
                <button type="submit">Сохранить пароль</button>
            </form>
        </div>
    </div>
</div>
@endsection
