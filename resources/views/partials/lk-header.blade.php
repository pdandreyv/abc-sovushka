{{-- Шапка ЛК: хлебные крошки и оранжевый блок подписок. Подключать на всех страницах ЛК. --}}
{{-- Передайте breadcrumbItems: массив [['label' => 'Главная', 'url' => url('/')], ['label' => 'Текущая', 'url' => null]]. Главная и кабинет — одна страница, пункт «Кабинет» не используем. --}}
@php
  $items = $breadcrumbItems ?? [];
  $status = lk_subscription_status();
  $hasSubscriptions = $status['hasSubscriptions'];
  $daysLeft = $status['daysLeft'];
@endphp
<div class="header">
  <div class="breadcrumbs">
    @foreach($items as $i => $item)
      @if($i > 0)<span aria-hidden="true"> / </span>@endif
      @if(!empty($item['url']))
        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
      @else
        <span>{{ $item['label'] }}</span>
      @endif
    @endforeach
  </div>
  <div class="header-icons">
    <img alt="Подписка" src="{{ asset('images/subscription_icon.png') }}"/>
    <a class="subscription-status subscription-status-link" href="{{ route('subscriptions.index') }}">
      @if($hasSubscriptions && $daysLeft !== null)
        {{ site_lang('lk_dashboard|status_left', 'Осталось') }} {{ $daysLeft }} {{ plural_ru($daysLeft, site_lang('lk_dashboard|days_1', 'день'), site_lang('lk_dashboard|days_2', 'дня'), site_lang('lk_dashboard|days_5', 'дней')) }} {{ site_lang('lk_dashboard|status_subscription', 'подписки: продлить / отменить') }}
      @else
        {{ site_lang('lk_dashboard|status_none', 'Подписок нет: выбрать / оформить') }}
      @endif
    </a>
    @php
    $maxChatUrl = 'https://max.ru/u/f9LHodD0cOKN6iyDGVIwGhlm5hmSuDifofKRTR9R4jmtBJqhr4qFtQh8WNs';
    $maxInitialText = auth()->check()
      ? rawurlencode('САЙТ-ПОДПИСКИ ID:' . auth()->id())
      : rawurlencode('САЙТ-ПОДПИСКИ');
    $maxLink = $maxChatUrl . (str_contains($maxChatUrl, '?') ? '&' : '?') . 'text=' . $maxInitialText;
  @endphp
    <a href="{{ $maxLink }}" target="_blank" rel="noopener noreferrer" class="header-help-link" title="{{ site_lang('lk_dashboard|help', 'Помощь') }}">
      <img alt="{{ site_lang('lk_dashboard|help', 'Помощь') }}" src="{{ asset('images/support_icon.png') }}"/>
    </a>
  </div>
</div>
