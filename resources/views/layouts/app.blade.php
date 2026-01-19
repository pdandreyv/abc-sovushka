<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Совушкина школа')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    @stack('styles')
</head>
<body>
    @yield('content')
    @php
        $currentUrl = isset($url) ? $url : (request()->segment(1) ?? '');
        $isAuthPage = request()->routeIs('login') || request()->routeIs('register') || request()->is('login') || request()->is('register');
        $hideFooter = in_array($currentUrl, ['politika-konfidentsialnosti', 'polzovatelskoe-soglashenie']) || $isAuthPage;
    @endphp
    @if(!$hideFooter)
    <footer class="dashboard-footer">
        <div class="footer-content">
            <div class="footer-copyright">
                © {{ date('Y') }} Совушкина школа
            </div>
            <div class="footer-links">
                <a href="{{ route('page.show', ['url' => 'politika-konfidentsialnosti']) }}" target="_blank" rel="noopener noreferrer">Политика конфиденциальности</a>
                <span class="footer-separator">|</span>
                <a href="{{ route('page.show', ['url' => 'polzovatelskoe-soglashenie']) }}" target="_blank" rel="noopener noreferrer">Пользовательское соглашение</a>
            </div>
        </div>
    </footer>
    @endif
    @stack('scripts')
</body>
</html>
