{{-- Общий сайдбар ЛК. Передайте $sidebarActive: 'profile' | 'dashboard' | 'subscriptions' | 'ideas' | ID уровня (число) для открытого раздела --}}
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
      <button type="button" onclick="window.location.href='{{ route('profile.show') }}'" class="{{ $active === 'profile' ? 'active' : '' }}">{{ site_lang('lk_menu|profile', 'Личные данные') }}</button>
      <button type="button" onclick="window.location.href='{{ route('portfolio.index') }}'" class="{{ $active === 'portfolio' ? 'active' : '' }}">{{ site_lang('lk_menu|portfolio', 'Портфолио') }}</button>
      <button type="button" onclick="window.location.href='{{ route('subscriptions.index') }}'" class="{{ $active === 'subscriptions' ? 'active' : '' }}">{{ site_lang('lk_menu|subscriptions', 'Подписки') }}</button>
      <button type="button" onclick="window.location.href='{{ route('ideas.index') }}'" class="{{ $active === 'ideas' ? 'active' : '' }}">{{ site_lang('lk_menu|ideas', 'Кладовая идей') }}</button>
      @foreach($openLevels ?? [] as $openLevel)
      <button type="button" onclick="window.location.href='{{ route('subjects.index', ['level' => $openLevel->id]) }}'" class="{{ $active === $openLevel->id || $active === (string) $openLevel->id ? 'active' : '' }}">{{ $openLevel->title }}</button>
      @endforeach
    </div>
  </div>
</div>
