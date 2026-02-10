{{-- Общий сайдбар ЛК. Передайте $sidebarActive: 'home' | 'profile' | 'dashboard' | 'subscriptions' | 'ideas' | ID уровня (число) для открытого раздела --}}
@php
  $active = $sidebarActive ?? null;
@endphp
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
      <button type="button" onclick="window.location.href='{{ route('dashboard') }}'" class="{{ $active === 'home' || $active === 'dashboard' ? 'active' : '' }}">{{ site_lang('lk_menu|home', 'Главная') }}</button>
      <button type="button" onclick="window.location.href='{{ route('profile.show') }}'" class="{{ $active === 'profile' ? 'active' : '' }}">{{ site_lang('lk_menu|profile', 'Личные данные') }}</button>
      <button type="button" onclick="window.location.href='{{ route('portfolio.index') }}'" class="{{ $active === 'portfolio' ? 'active' : '' }}">{{ site_lang('lk_menu|portfolio', 'Портфолио') }}</button>
      @php $mySubscriptionLevels = lk_my_subscription_levels(); @endphp
      @if($mySubscriptionLevels->isNotEmpty())
      <div class="menu-subscriptions">
        <div class="menu-section-title">{{ site_lang('lk_menu|my_subscriptions', 'Мои подписки') }}</div>
        <div class="menu-submenu">
          @foreach($mySubscriptionLevels as $level)
          <button type="button" onclick="window.location.href='{{ route('subjects.index', ['level' => $level->id]) }}'" class="{{ $active === $level->id || $active === (string) $level->id ? 'active' : '' }}">{{ $level->title }}</button>
          @endforeach
        </div>
      </div>
      @endif
      <button type="button" onclick="window.location.href='{{ route('subscriptions.index') }}'" class="{{ $active === 'subscriptions' ? 'active' : '' }}">{{ site_lang('lk_menu|subscriptions', 'Оформить подписку') }}</button>
      <button type="button" onclick="window.location.href='{{ route('ideas.index') }}'" class="{{ $active === 'ideas' ? 'active' : '' }}">{{ site_lang('lk_menu|ideas', 'Кладовая идей') }}</button>
      @foreach($openLevels ?? [] as $openLevel)
      <button type="button" onclick="window.location.href='{{ route('subjects.index', ['level' => $openLevel->id]) }}'" class="{{ $active === $openLevel->id || $active === (string) $openLevel->id ? 'active' : '' }}">{{ $openLevel->title }}</button>
      @endforeach
    </div>
  </div>
</div>
