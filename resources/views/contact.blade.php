<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Liên hệ</title>
    <style>
        :root { color-scheme: light dark; --bg: #f6f7f9; --card: #fff; --text: #1d2330; --muted: #646b7a; --accent: #2557d6; --border: #e1e4ea; --ok: #1a7f37; --ok-bg: #e6f4ea; --err: #c62828; --err-bg: #fdecea; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #14171f; --card: #1d212c; --text: #e8eaf0; --muted: #9aa1b2; --accent: #7aa2ff; --border: #2c3140; --ok: #6fd08c; --ok-bg: #16301f; --err: #ff8a80; --err-bg: #3a1a1a; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
        header, main { max-width: 560px; margin: 0 auto; padding: 0 16px; }
        header { padding-top: 32px; padding-bottom: 8px; }
        h1 { margin: 0; font-size: 1.6rem; }
        a { color: var(--accent); }
        form { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 16px 20px 20px; margin: 16px 0; }
        label { display: block; margin-top: 14px; font-weight: 600; font-size: .95rem; }
        .opt { color: var(--muted); font-weight: 400; }
        input, select, textarea { width: 100%; margin-top: 4px; padding: 9px 10px; font: inherit; color: var(--text); background: var(--bg); border: 1px solid var(--border); border-radius: 6px; }
        input:focus, select:focus, textarea:focus { outline: 2px solid var(--accent); outline-offset: 0; }
        .invalid { border-color: var(--err); }
        .field-error { color: var(--err); font-size: .85rem; margin: 4px 0 0; }
        button { margin-top: 18px; padding: 10px 22px; font: inherit; font-weight: 600; color: #fff; background: var(--accent); border: 0; border-radius: 6px; cursor: pointer; }
        .alert { border-radius: 8px; padding: 10px 14px; margin: 16px 0 0; }
        .alert-ok { background: var(--ok-bg); color: var(--ok); }
        .alert-err { background: var(--err-bg); color: var(--err); }
    </style>
</head>
<body>
<header>
    <h1>Liên hệ</h1>
    <a href="{{ url('/') }}">&larr; Về trang chủ</a>
</header>
<main>
    @if (session('success'))
        <div class="alert alert-ok" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-err" role="alert">Vui lòng kiểm tra lại các trường được đánh dấu.</div>
    @endif

    <form method="POST" action="{{ route('contact.store') }}" novalidate>
        @csrf

        <label for="name">Họ tên</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="100" class="@error('name') invalid @enderror">
        @error('name') <p class="field-error">{{ $message }}</p> @enderror

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150" class="@error('email') invalid @enderror">
        @error('email') <p class="field-error">{{ $message }}</p> @enderror

        <label for="phone">Số điện thoại <span class="opt">(không bắt buộc)</span></label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" class="@error('phone') invalid @enderror">
        @error('phone') <p class="field-error">{{ $message }}</p> @enderror

        <label for="topic">Chủ đề</label>
        <select id="topic" name="topic" class="@error('topic') invalid @enderror">
            <option value="">-- Chọn chủ đề --</option>
            @foreach ($topics as $value => $label)
                <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('topic') <p class="field-error">{{ $message }}</p> @enderror

        <label for="message">Nội dung</label>
        <textarea id="message" name="message" rows="5" maxlength="2000" class="@error('message') invalid @enderror">{{ old('message') }}</textarea>
        @error('message') <p class="field-error">{{ $message }}</p> @enderror

        <button type="submit">Gửi liên hệ</button>
    </form>
</main>
</body>
</html>
