<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Tin tức</title>
    <style>
        :root { color-scheme: light dark; --bg: #f6f7f9; --card: #fff; --text: #1d2330; --muted: #646b7a; --accent: #2557d6; --border: #e1e4ea; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #14171f; --card: #1d212c; --text: #e8eaf0; --muted: #9aa1b2; --accent: #7aa2ff; --border: #2c3140; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
        header, main { max-width: 760px; margin: 0 auto; padding: 0 16px; }
        header { padding-top: 32px; padding-bottom: 8px; }
        h1 { margin: 0; font-size: 1.6rem; }
        .env { color: var(--muted); font-size: .85rem; }
        article { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 16px 20px; margin: 16px 0; }
        article h2 { margin: 0 0 4px; font-size: 1.15rem; color: var(--accent); }
        time { color: var(--muted); font-size: .85rem; }
        p { margin: 8px 0 0; }
        .empty { color: var(--muted); text-align: center; padding: 48px 0; }
    </style>
</head>
<body>
<header>
    <h1>{{ config('app.name') }} - Tin tức</h1>
    <div class="env">Môi trường: {{ app()->environment() }} - TRẦN VĂN TUẤN</div>
</header>
<main>
    @forelse ($news as $item)
        <article>
            <h2>{{ $item->title }}</h2>
            <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('d/m/Y H:i') }}</time>
            <p>{{ $item->summary }}</p>
        </article>
    @empty
        <p class="empty">Không có tin tức nào.</p>
    @endforelse
</main>
</body>
</html>
