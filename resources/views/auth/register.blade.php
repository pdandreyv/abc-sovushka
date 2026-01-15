@extends('layouts.app')

@section('title', 'Совушкина школа — Регистрация')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="left">
        <img src="{{ asset('images/logo.png') }}" alt="Логотип" />
        <div class="welcome-text">
            <h3>Добро пожаловать в Совушкину школу!</h3>
            <p>Готовые уроки, рабочие листы, презентации и бонусные материалы для учителей начальных классов, воспитателей и педагогов дополнительного образования. Доступ 24/7 — экономьте время и работайте с удовольствием!</p>
        </div>
    </div>
    <div class="right">
        <div class="form-box">
            <div class="tab-switcher">
                <div id="tab-login" onclick="switchTab('login')">Вход</div>
                <div id="tab-register" class="active" onclick="switchTab('register')">Регистрация</div>
            </div>
            <div id="login-box" style="display:none">
                <h2>Вход в личный кабинет</h2>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <input type="email" name="email" placeholder="Email" required />
                    <input type="password" name="password" placeholder="Пароль" required />
                    <button type="submit">Войти</button>
                </form>
                <div class="social-login">
                    <p>или через соцсети:</p>
                    <div class="icons">
                        <button class="icon-btn" type="button"><img src="{{ asset('images/vk-pin.png') }}" alt="VK"></button>
                        <button class="icon-btn" type="button"><img src="{{ asset('images/yandex-pin.png') }}" alt="Yandex"></button>
                        <button class="icon-btn" type="button"><img src="{{ asset('images/ok-pin.png') }}" alt="OK"></button>
                    </div>
                </div>
                <div class="form-footer">
                    <p><a href="#">Политика конфиденциальности</a></p>
                    <p><a href="#">Пользовательское соглашение</a></p>
                </div>
            </div>

            <div id="register-box">
                <h2>Регистрация</h2>
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <input type="text" name="last_name" placeholder="Фамилия" value="{{ old('last_name') }}" required />
                    <input type="text" name="first_name" placeholder="Имя" value="{{ old('first_name') }}" required />
                    <input type="text" name="middle_name" placeholder="Отчество" value="{{ old('middle_name') }}" />
                    <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required />
                    <input type="password" name="password" placeholder="Пароль" required />
                    <input type="password" name="password_confirmation" placeholder="Повторите пароль" required />
                    <button type="submit">Зарегистрироваться</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tab) {
        const loginTab = document.getElementById('tab-login');
        const registerTab = document.getElementById('tab-register');
        const loginBox = document.getElementById('login-box');
        const registerBox = document.getElementById('register-box');

        if (tab === 'login') {
            loginTab.classList.add('active');
            registerTab.classList.remove('active');
            loginBox.style.display = 'block';
            registerBox.style.display = 'none';
        } else {
            loginTab.classList.remove('active');
            registerTab.classList.add('active');
            loginBox.style.display = 'none';
            registerBox.style.display = 'block';
        }
    }
</script>
@endsection
