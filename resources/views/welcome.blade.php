@extends('layouts.site')

@section('title', config('app.name').' - Trần Văn Tuấn')

@section('content')
<section class="hero">
    <span class="eyebrow reveal"><i class="dot"></i>DevOps · Cloud · Automation</span>
    <h1 class="reveal d1"><span class="grad">Trần Văn Tuấn</span></h1>
    <p class="role reveal d2">Xây dựng &amp; vận hành ứng dụng web từ commit đầu tiên đến production</p>
    <p class="lead reveal d2">Tự động hóa quy trình build, test, đóng gói và triển khai để mỗi lần phát hành đều nhanh, an toàn và lặp lại được.</p>
    <div class="cta reveal d3">
        <a class="btn btn-primary" href="{{ route('contact.create') }}">Liên hệ với tôi &rarr;</a>
        <a class="btn btn-ghost" href="{{ route('notepad') }}">Mở Notepad</a>
    </div>
</section>

<section class="section" id="cong-nghe">
    <div class="section-head">
        <div class="kicker">Công nghệ</div>
        <h2>Bộ công cụ tôi dùng hằng ngày</h2>
    </div>
    <div class="stack">
        <span>Laravel</span>
        <span>PHP</span>
        <span>Docker</span>
        <span>Jenkins</span>
        <span>GitHub Actions</span>
        <span>AWS EC2</span>
        <span>Amazon ECR</span>
        <span>MySQL</span>
        <span>Nginx</span>
        <span>CloudWatch</span>
    </div>
</section>

<section class="section" id="goc-lam-viec">
    <div class="section-head">
        <div class="kicker">Góc làm việc</div>
        <h2>Công cụ &amp; kết nối</h2>
    </div>
    <div class="grid grid-2">
        <a class="card" href="{{ route('notepad') }}">
            <div class="icon" aria-hidden="true">📝</div>
            <h3>Notepad</h3>
            <p>Ghi chú nhiều tab kiểu Notepad++. Dán vào là tự lưu, lần sau mở lại vẫn còn nguyên.</p>
            <span class="more">Mở Notepad &rarr;</span>
        </a>
        <a class="card" href="{{ route('contact.create') }}">
            <div class="icon" aria-hidden="true">✉️</div>
            <h3>Liên hệ</h3>
            <p>Có dự án, câu hỏi hay muốn hợp tác? Gửi tin nhắn cho tôi qua biểu mẫu liên hệ.</p>
            <span class="more">Gửi tin nhắn &rarr;</span>
        </a>
    </div>
</section>

<section class="section news" id="nhat-ky">
    <div class="section-head">
        <div class="kicker">Nhật ký</div>
        <h2>Tin tức &amp; cập nhật</h2>
    </div>
    @if ($news->isEmpty())
        <p class="empty">Chưa có bài viết nào.</p>
    @else
        <div class="grid grid-2">
            @foreach ($news as $item)
                <article class="card">
                    <time datetime="{{ $item->published_at->toIso8601String() }}">{{ $item->published_at->format('d/m/Y') }}</time>
                    <h3>{{ $item->title }}</h3>
                    <p>{{ $item->summary }}</p>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="cta-band">
    <h2>Cùng làm điều gì đó <span class="grad">thật ngầu</span>?</h2>
    <p>Hãy để lại lời nhắn, tôi sẽ phản hồi sớm nhất có thể.</p>
    <a class="btn btn-primary" href="{{ route('contact.create') }}">Bắt đầu cuộc trò chuyện &rarr;</a>
</section>
@endsection

@push('scripts')
<script>
// Đốm sáng đi theo con trỏ trên các thẻ .card
document.querySelectorAll('.card').forEach(function (c) {
    c.addEventListener('pointermove', function (e) {
        var r = c.getBoundingClientRect();
        c.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        c.style.setProperty('--my', (e.clientY - r.top) + 'px');
    });
});
</script>
@endpush
