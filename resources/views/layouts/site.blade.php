<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="theme-color" content="#070a12">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @stack('styles')
</head>
<body class="@yield('body_class')">
<div class="bg" aria-hidden="true">
    <span class="orb orb-1"></span>
    <span class="orb orb-2"></span>
    <span class="orb orb-3"></span>
</div>

<nav class="nav" aria-label="Menu chính">
    <div class="nav-inner">
        <a class="brand" href="{{ url('/') }}">
            <img src="{{ asset('favicon.svg') }}" alt="" width="28" height="28">
            <span>Trần Văn Tuấn</span>
        </a>
        <ul class="menu">
            <li><a href="{{ url('/') }}" @class(['active' => request()->is('/')])>Trang chủ</a></li>
            <li><a href="{{ route('contact.create') }}" @class(['active' => request()->routeIs('contact.*')])>Liên hệ</a></li>
            <li><a href="{{ route('notepad') }}" @class(['active' => request()->routeIs('notepad')])>Notepad</a></li>
        </ul>
    </div>
</nav>

<main class="@yield('main_class')">
    @yield('content')
</main>

@hasSection('hide_footer')
@else
<footer class="footer">
    <div class="footer-inner">
        <span>&copy; {{ date('Y') }} Trần Văn Tuấn</span>
        <span class="chip"><i class="dot"></i>{{ app()->environment() }}</span>
    </div>
</footer>
@endif

@stack('scripts')
</body>
</html>
