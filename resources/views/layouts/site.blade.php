<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Home')) | {{ $siteSettings->get('site.office_title')?->localized('value') }}</title>
    <link rel="icon" href="/assets/favicon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $safeUrl = function ($url) {
        if (!is_string($url) || $url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) return '#';
        $safe = (str_starts_with($url, '/') && !str_starts_with($url, '//')) || str_starts_with($url, '#') || str_starts_with($url, '?')
            || preg_match('/^(?:https?:\/\/|mailto:|tel:)/i', $url);
        return $safe ? $url : '#';
    };
    $localizedUrl = function ($url) use ($safeUrl) {
        $url = $safeUrl($url);
        if (app()->getLocale() === 'en' && str_starts_with($url, '/') && !preg_match('#^/en(?:/|[?#]|$)#', $url)) return '/en'.$url;
        return $url;
    };
    $mediaUrl = $safeUrl;
    $setting = fn ($key, $default = '') => $siteSettings->get($key)?->localized('value') ?: $default;
@endphp
<a class="skip-link" href="#main-content">{{ __('Skip to main content') }}</a>
<div class="container">
    <div class="header"><div class="droppable">
        @include('site.partials.topbar')
        @include('site.partials.banner')
        @include('site.partials.menu')
    </div></div>
    <div class="wrapper">
        <main class="body"><div class="droppable" id="main-content">@yield('content')</div></main>
        @include('site.partials.sidebar')
    </div>
    @include('site.partials.footer')
</div>
@include('site.partials.accessibility')
</body>
</html>
