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
        $isAuthPage = request()->routeIs('login')
            || request()->routeIs('register')
            || request()->is('login')
            || request()->is('register')
            || request()->is('/');
        $menuPages = \App\Models\Page::getMenuPages();
        $hideFooter = $menuPages->isEmpty() || $isAuthPage;
    @endphp
    @if(!$hideFooter)
    <footer class="dashboard-footer">
        <div class="footer-content">
            <div class="footer-copyright">
                © {{ date('Y') }} Совушкина школа
            </div>
            <div class="footer-links">
                @foreach($menuPages as $index => $page)
                    @if($index > 0)
                        <span class="footer-separator">|</span>
                    @endif
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
                    <a href="{{ $pageUrl }}" @if($isExternal) target="_blank" rel="noopener noreferrer" @endif>{{ $page->name }}</a>
                @endforeach
            </div>
        </div>
    </footer>
    @endif
    @stack('scripts')
</body>
</html>
