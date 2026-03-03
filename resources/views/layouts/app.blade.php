<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Совушкина школа')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    @stack('styles')
    <style>
      /* Кнопка «Вверх» — появляется при прокрутке вниз */
      .scroll-top-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 48px;
        height: 48px;
        border: none;
        border-radius: 50%;
        background-color: #00bf63;
        color: #fff;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: opacity 0.25s ease, transform 0.2s ease, visibility 0.25s ease;
        z-index: 1000;
      }
      .scroll-top-btn[hidden] {
        visibility: hidden;
        opacity: 0;
        pointer-events: none;
      }
      .scroll-top-btn:hover {
        transform: scale(1.08);
        background-color: #00a356;
      }
      .scroll-top-btn:focus {
        outline: 2px solid #00bf63;
        outline-offset: 2px;
      }
    </style>
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
    <button type="button" id="scrollTopBtn" class="scroll-top-btn" title="Наверх" aria-label="Прокрутить страницу вверх" hidden>
      <span aria-hidden="true">↑</span>
    </button>
    <script>
      (function() {
        var btn = document.getElementById("scrollTopBtn");
        if (!btn) return;
        var main = document.querySelector(".main");
        var scrollEl = main || window;
        function updateBtn() {
          var top = main ? main.scrollTop : window.scrollY;
          if (top > 300) {
            btn.removeAttribute("hidden");
          } else {
            btn.setAttribute("hidden", "");
          }
        }
        scrollEl.addEventListener("scroll", updateBtn, { passive: true });
        btn.addEventListener("click", function() {
          if (main) {
            main.scrollTo({ top: 0, behavior: "smooth" });
          } else {
            window.scrollTo({ top: 0, behavior: "smooth" });
          }
        });
      })();
    </script>
    @stack('scripts')
</body>
</html>
