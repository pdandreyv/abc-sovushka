@extends('layouts.app')

@section('title', 'Совушкина школа — Вход / Регистрация')

@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('css/auth.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="left">
        <img src="{{ asset('images/logo.png') }}" alt="Логотип" />
        <div class="welcome-text">
            <h3>{{ site_lang('auth|welcome_title', 'Добро пожаловать в Совушкину школу!') }}</h3>
            <p>{{ site_lang('auth|welcome_text', 'Готовые уроки, рабочие листы, презентации и бонусные материалы для учителей начальных классов, воспитателей и педагогов дополнительного образования. Доступ 24/7 — экономьте время и работайте с удовольствием!') }}</p>
        </div>
    </div>
    <div class="right">
        <div class="form-box">
                <div class="tab-switcher">
                    <div id="tab-login" class="active" onclick="switchTab('login')">{{ site_lang('auth|tab_login', 'Вход') }}</div>
                    <div id="tab-register" onclick="switchTab('register')">{{ site_lang('auth|tab_register', 'Регистрация') }}</div>
                </div>
            <div id="login-box">
                <h2>{{ site_lang('auth|login_heading', 'Вход в личный кабинет') }}</h2>
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('login') }}" id="login-form">
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
                    <input type="email" name="email" placeholder="{{ site_lang('auth|login_email_placeholder', 'Email') }}" value="{{ old('email') }}" required autofocus class="@error('email') input-error @enderror" />
                    @error('email')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <input type="password" name="password" placeholder="{{ site_lang('auth|login_password_placeholder', 'Пароль') }}" required class="@error('password') input-error @enderror" />
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <button type="submit">{{ site_lang('auth|login_button', 'Войти') }}</button>
                </form>
                <div class="social-login">
                    <p>{{ site_lang('auth|social_title', 'или через соцсети:') }}</p>
                    <div class="icons">
                        <a href="{{ route('social.redirect', ['provider' => 'yandex']) }}" class="icon-btn" type="button"><img src="{{ asset('images/yandex-pin.png') }}" alt="Yandex"></a>
                    </div>
                    <div
                        id="vkid-oauth-list"
                        class="vkid-oauth-list"
                        data-vkid-app="{{ (int) config('services.vkontakte.client_id') }}"
                        data-vkid-redirect="{{ url('/') }}"
                        data-vkid-callback="{{ route('social.vkid.callback') }}"
                    ></div>
                    <div id="vkid-error" class="alert alert-danger" style="display: none;"></div>
                </div>
                <div class="form-footer">
                    @php
                        $menuPages = \App\Models\Page::getMenuPages();
                    @endphp
                    @foreach($menuPages as $page)
                        @php
                            $pageUrl = (str_starts_with($page->url, 'http://') || str_starts_with($page->url, 'https://')) 
                                ? $page->url 
                                : route('page.show', ['url' => $page->url]);
                            $pageHost = null;
                            if (str_starts_with($pageUrl, 'http://') || str_starts_with($pageUrl, 'https://')) {
                                $pageHost = parse_url($pageUrl, PHP_URL_HOST);
                            }
                            $isExternal = $pageHost && $pageHost !== request()->getHost();
                        @endphp
                        <p><a href="{{ $pageUrl }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>{{ $page->name }}</a></p>
                    @endforeach
                </div>
            </div>

            <div id="register-box" style="display:none">
                <h2>{{ site_lang('auth|register_heading', 'Регистрация') }}</h2>
                <form id="register-form" method="POST" action="{{ route('register') }}">
                    @csrf
                    @if ($errors->any())
                        <div id="server-validation-errors" class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div id="js-validation-errors" class="alert alert-danger" style="display: none;">
                        <ul id="js-validation-list"></ul>
                    </div>
                    <input type="text" name="last_name" id="last_name" placeholder="{{ site_lang('auth|register_last_name', 'Фамилия') }}" value="{{ old('last_name') }}" required class="@error('last_name') input-error @enderror" />
                    @error('last_name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <input type="text" name="first_name" id="first_name" placeholder="{{ site_lang('auth|register_first_name', 'Имя') }}" value="{{ old('first_name') }}" required class="@error('first_name') input-error @enderror" />
                    @error('first_name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <input type="text" name="middle_name" id="middle_name" placeholder="{{ site_lang('auth|register_middle_name', 'Отчество') }}" value="{{ old('middle_name') }}" class="@error('middle_name') input-error @enderror" />
                    @error('middle_name')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <input type="email" name="email" id="email" placeholder="{{ site_lang('auth|register_email', 'Email') }}" value="{{ old('email') }}" required class="@error('email') input-error @enderror" />
                    @error('email')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <input type="password" name="password" id="password" placeholder="{{ site_lang('auth|register_password', 'Пароль') }}" required class="@error('password') input-error @enderror" />
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="{{ site_lang('auth|register_password_confirm', 'Повторите пароль') }}" required />
                    <button type="submit">{{ site_lang('auth|register_button', 'Зарегистрироваться') }}</button>
                    <p class="form-legal">{!! site_lang('auth|register_consent', 'Регистрируясь, я принимаю условия Пользовательского соглашения об использовании Личного кабинета Клиента и соглашаюсь с Политикой обработки персональных данных.') !!}</p>
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
        const formBox = document.querySelector('.form-box');

        if (tab === 'login') {
            loginTab.classList.add('active');
            registerTab.classList.remove('active');
            loginBox.style.display = 'block';
            registerBox.style.display = 'none';
            
            // Скрываем ВСЕ проявления ошибок во всем контейнере формы при переключении на "Вход"
            if (formBox) {
                // Скрываем все блоки с классом alert-danger (общие блоки ошибок)
                const allAlerts = formBox.querySelectorAll('.alert-danger');
                allAlerts.forEach(function(alert) {
                    alert.style.display = 'none';
                });
                
                // Скрываем все элементы с классом field-error (ошибки под полями)
                const allFieldErrors = formBox.querySelectorAll('.field-error');
                allFieldErrors.forEach(function(error) {
                    error.style.display = 'none';
                });
                
                // Убираем класс input-error со всех полей ввода (красные рамки)
                const allInputs = formBox.querySelectorAll('input');
                allInputs.forEach(function(input) {
                    input.classList.remove('input-error');
                });
                
                // Очищаем список ошибок JavaScript валидации
                const validationList = document.getElementById('js-validation-list');
                if (validationList) {
                    validationList.innerHTML = '';
                }
            }
        } else {
            loginTab.classList.remove('active');
            registerTab.classList.add('active');
            loginBox.style.display = 'none';
            registerBox.style.display = 'block';
        }
    }

    // Переключение на вкладку регистрации, если есть ошибки валидации регистрации
    @if($errors->any() && old('last_name'))
        switchTab('register');
    @endif
    
    // Убеждаемся, что форма входа видна при ошибках входа
    @if($errors->any() && !old('last_name') && old('email'))
        // Форма входа уже видна, ничего не делаем
    @endif

    // Валидация формы регистрации
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('register-form');
        const validationErrors = document.getElementById('js-validation-errors');
        const validationList = document.getElementById('js-validation-list');

        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Очистка предыдущих ошибок
                validationList.innerHTML = '';
                validationErrors.style.display = 'none';
                
                const errors = [];
                
                // Получение значений полей
                const lastName = document.getElementById('last_name').value.trim();
                const firstName = document.getElementById('first_name').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const passwordConfirmation = document.getElementById('password_confirmation').value;
                
                // Проверка обязательных полей
                if (!lastName) {
                    errors.push('Поле "Фамилия" обязательно для заполнения.');
                }
                
                if (!firstName) {
                    errors.push('Поле "Имя" обязательно для заполнения.');
                }
                
                if (!email) {
                    errors.push('Поле "Email" обязательно для заполнения.');
                } else {
                    // Проверка формата email
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        errors.push('Email должен быть действительным адресом электронной почты.');
                    }
                }
                
                if (!password) {
                    errors.push('Поле "Пароль" обязательно для заполнения.');
                } else {
                    // Проверка длины пароля (минимум 8 символов)
                    if (password.length < 8) {
                        errors.push('Пароль должен содержать минимум 8 символов.');
                    }
                }
                
                if (!passwordConfirmation) {
                    errors.push('Поле "Повторите пароль" обязательно для заполнения.');
                }
                
                // Проверка совпадения паролей
                if (password && passwordConfirmation && password !== passwordConfirmation) {
                    errors.push('Пароли не совпадают.');
                }
                
                // Если есть ошибки, показываем их
                if (errors.length > 0) {
                    errors.forEach(function(error) {
                        const li = document.createElement('li');
                        li.textContent = error;
                        validationList.appendChild(li);
                    });
                    validationErrors.style.display = 'block';
                    
                    // Прокрутка к ошибкам
                    validationErrors.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    // Если ошибок нет, отправляем форму
                    registerForm.submit();
                }
            });
        }
    });
</script>

@push('scripts')
<script src="https://unpkg.com/@vkid/sdk@<3.0.0/dist-sdk/umd/index.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!('VKIDSDK' in window)) {
            return;
        }

        const container = document.getElementById('vkid-oauth-list');
        if (!container) {
            return;
        }

        const appId = Number(container.dataset.vkidApp || 0);
        const redirectUrl = container.dataset.vkidRedirect || '';
        const callbackUrl = container.dataset.vkidCallback || '';

        if (!appId || !redirectUrl || !callbackUrl) {
            return;
        }

        const VKID = window.VKIDSDK;

        VKID.Config.init({
            app: appId,
            redirectUrl: redirectUrl,
            responseMode: VKID.ConfigResponseMode.Callback,
            source: VKID.ConfigSource.LOWCODE,
            scope: '',
        });

        const oAuth = new VKID.OAuthList();
        const errorContainer = document.getElementById('vkid-error');

        oAuth.render({
            container: container,
            oauthList: ['vkid', 'ok_ru', 'mail_ru'],
        })
        .on(VKID.WidgetEvents.ERROR, vkidOnError)
        .on(VKID.OAuthListInternalEvents.LOGIN_SUCCESS, function(payload) {
            const code = payload.code;
            const deviceId = payload.device_id;
            const provider = payload.provider || payload.auth_provider || payload.service || payload.source || null;

            VKID.Auth.exchangeCode(code, deviceId)
                .then(function(data) {
                    return finishVkidAuth(data.access_token, provider);
                })
                .catch(vkidOnError);
        });

        function finishVkidAuth(accessToken, provider) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Content-Type': 'application/json',
            };
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
            }

            return fetch(callbackUrl, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    access_token: accessToken,
                    provider: provider,
                }),
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                if (result.ok && result.data.redirect) {
                    window.location.href = result.data.redirect;
                    return;
                }

                throw new Error(result.data.message || 'Ошибка авторизации через VK ID.');
            })
            .catch(vkidOnError);
        }

        function vkidOnError(error) {
            if (errorContainer) {
                errorContainer.textContent = 'Ошибка авторизации через VK/OK. Попробуйте еще раз.';
                errorContainer.style.display = 'block';
            }

            if (window.console && console.error) {
                console.error(error);
            }
        }
    });
</script>
@endpush
@endsection
