@extends('layouts.app')

@section('title', $page->title ?: $page->name)

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <article>
        @if($page->h1)
            <h1 style="font-size: 2rem; font-weight: bold; margin-bottom: 1rem;">{{ $page->h1 }}</h1>
        @elseif($page->name)
            <h1 style="font-size: 2rem; font-weight: bold; margin-bottom: 1rem;">{{ $page->name }}</h1>
        @endif

        @if($page->description)
            <p style="color: #666; margin-bottom: 1.5rem;">{{ $page->description }}</p>
        @endif

        @if($content)
            <div style="line-height: 1.6;">
                {!! $content !!}
            </div>
        @else
            <p style="color: #999;">Контент страницы отсутствует.</p>
        @endif
    </article>
</div>
@endsection
