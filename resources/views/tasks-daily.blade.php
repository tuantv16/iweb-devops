@extends('layouts.site')

@section('title', config('app.name').' - Task mỗi ngày')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tasks.css') }}?v={{ filemtime(public_path('css/tasks.css')) }}">
@endpush

@php
    $thu = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    $hours = fn ($h) => rtrim(rtrim(number_format($h, 1), '0'), '.');
    $dateStr = $date->toDateString();
@endphp

@section('content')
<div class="tasks">
    <div class="head">
        <h1><span class="grad">Tasks</span> · {{ config('services.redmine.project') }}</h1>
        @isset($fetched_at)
            <span class="hint">Cập nhật {{ $fetched_at->timezone(config('services.redmine.timezone'))->format('H:i d/m') }} · <a href="{{ route('tasks.daily', ['date' => $dateStr, 'refresh' => 1]) }}">Làm mới</a></span>
        @endisset
    </div>

    @include('partials.task-tabs')

    <form class="daynav" method="get" action="{{ route('tasks.daily') }}">
        <a class="btn btn-ghost" href="{{ route('tasks.daily', ['date' => $date->copy()->subDay()->toDateString()]) }}" aria-label="Ngày trước">←</a>
        <input type="date" name="date" value="{{ $dateStr }}" max="{{ $today->toDateString() }}" aria-label="Chọn ngày" onchange="this.form.submit()">
        <a class="btn btn-ghost" href="{{ route('tasks.daily', ['date' => $date->copy()->addDay()->toDateString()]) }}" aria-label="Ngày sau" @if ($date->gte($today)) hidden @endif>→</a>
        @if (! $date->isSameDay($today))
            <a class="btn btn-ghost" href="{{ route('tasks.daily') }}">Hôm nay</a>
        @endif
        <span class="cur">{{ $thu[$date->dayOfWeek] }}, {{ $date->format('d/m/Y') }}</span>
        <noscript><button class="btn btn-ghost" type="submit">Xem</button></noscript>
    </form>

    @isset($error)
        <div class="err" role="alert">{{ $error }}</div>
    @else
        <div class="sums">
            <div class="sum"><b>{{ $totals['members'] }}</b><span>Người có log công việc</span></div>
            <div class="sum"><b>{{ $totals['tasks'] }}</b><span>Lượt task trong ngày</span></div>
            <div class="sum good"><b>{{ $totals['done'] }}<small>/{{ $totals['tasks'] }}</small></b><span>Đã hoàn thành (Resolved / Closed)</span></div>
            <div class="sum"><b>{{ $hours($totals['hours']) }}h</b><span>Tổng giờ log</span></div>
        </div>

        @if (count($members) === 0)
            <p class="hint">Không có log công việc nào trong 7 ngày kết thúc ngày {{ $date->format('d/m') }}.</p>
        @else
            {{-- Tổng quan 7 ngày: bấm vào ô để chuyển sang ngày đó; bấm tiêu đề ngày để sắp xếp theo số task hoàn thành --}}
            <div class="table-wrap">
                <table class="week">
                    <thead>
                    <tr>
                        <th>Thành viên</th>
                        @foreach ($days as $d)
                            @php $dd = \Illuminate\Support\Carbon::parse($d); @endphp
                            <th @class(['sel' => $d === $dateStr]) aria-sort="none">
                                <button type="button" class="sortbtn" data-col="{{ $loop->iteration }}" title="Sắp xếp theo số task hoàn thành ngày {{ $dd->format('d/m') }}">
                                    {{ $thu[$dd->dayOfWeek] }}<br>{{ $dd->format('d/m') }}<i class="arrow" aria-hidden="true"></i>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($members as $m)
                        <tr @class(['sel' => count($m['tasks']) > 0]) data-name="{{ $m['name'] }}">
                            <td>{{ $m['name'] }}</td>
                            @foreach ($days as $d)
                                @php $c = $m['by_day'][$d] ?? null; @endphp
                                @if ($c)
                                    <td @class(['sel-col' => $d === $dateStr]) data-done="{{ $c['done'] }}" data-tasks="{{ $c['tasks'] }}"><a href="{{ route('tasks.daily', ['date' => $d]) }}">{{ $c['tasks'] }} task<small class="{{ $c['done'] > 0 ? 'ok' : '' }}">{{ $c['done'] }} xong · {{ $hours($c['hours']) }}h</small></a></td>
                                @else
                                    <td @class(['zero', 'sel-col' => $d === $dateStr]) data-done="0" data-tasks="0">–</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="detail-head">
                <h2>Chi tiết ngày {{ $date->format('d/m/Y') }}</h2>
                <div class="sortbar" role="group" aria-label="Sắp xếp chi tiết">
                    <span>Sắp xếp:</span>
                    <button type="button" class="on" data-sort="tasks">Số task</button>
                    <button type="button" data-sort="done">Số hoàn thành</button>
                </div>
            </div>

            <div class="legend">
                @foreach ($groups as $key => $label)
                    <span><i class="g-{{ $key }}"></i>{{ $label }}</span>
                @endforeach
                <span>· Bấm vào tên để xem task, bấm task để mở Redmine</span>
                <span>· "Hoàn thành" = task đang ở trạng thái Resolved hoặc Closed. Trạng thái và % là của task <b>hiện tại</b>, không phải lúc log</span>
            </div>

            @php $any = false; @endphp
            <div id="detail">
            @foreach ($members as $m)
                @continue(count($m['tasks']) === 0)
                @php $any = true; @endphp
                <details class="member daily" data-name="{{ $m['name'] }}" data-tasks="{{ count($m['tasks']) }}" data-done="{{ $m['done'] }}" data-hours="{{ $m['hours'] }}">
                    <summary>
                        <div>
                            <div class="name"><span class="rank">{{ $loop->iteration }}</span>{{ $m['name'] }}</div>
                            <div class="meta">{{ $hours($m['hours']) }}h đã log</div>
                        </div>
                        <div></div>
                        <div class="nums">
                            <b>{{ count($m['tasks']) }}</b> task
                            · <span class="{{ $m['done'] > 0 ? 'okc' : '' }}"><b>{{ $m['done'] }}</b> hoàn thành</span>
                        </div>
                    </summary>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Task</th><th>Trạng thái</th><th>Tiến độ</th><th>Giờ</th></tr></thead>
                            <tbody>
                            @foreach ($m['tasks'] as $t)
                                <tr>
                                    <td>
                                        <a class="issue" href="{{ $redmine->issueUrl($t['id']) }}" target="_blank" rel="noopener noreferrer"><b>#{{ $t['id'] }}</b> {{ $t['subject'] }}</a>
                                        @if ($t['done'])<span class="done-badge">Hoàn thành</span>@endif
                                        @if ($t['closed_today'])<span class="done-badge closed">Đóng hôm nay</span>@endif
                                        @if ($t['activities'])<div class="acts">{{ implode(', ', $t['activities']) }}</div>@endif
                                        @if ($t['notes'])
                                            <ul class="notes">
                                                @foreach ($t['notes'] as $note)<li>{{ $note }}</li>@endforeach
                                            </ul>
                                        @endif
                                    </td>
                                    <td class="tag"><i class="g-{{ $t['group'] }}"></i>{{ $t['status'] ?: '—' }}</td>
                                    <td class="pct">{{ $t['done_ratio'] }}%</td>
                                    <td class="pct">{{ $hours($t['hours']) }}h</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endforeach
            </div>

            @unless ($any)
                <p class="hint">Ngày {{ $date->format('d/m') }} không có ai log công việc (cuối tuần hoặc chưa ai nhập).</p>
            @endunless
        @endif
    @endisset
</div>
@endsection


@push('scripts')
<script>
(function () {
    // keys: các hàm lấy giá trị số, ưu tiên từ trái sang phải (lớn lên trước); hoà thì theo tên A-Z.
    function by(keys, dir) {
        return function (a, b) {
            for (var i = 0; i < keys.length; i++) {
                var d = keys[i](b) - keys[i](a);
                if (d !== 0) return d * dir;
            }
            return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'), 'vi');
        };
    }
    function reorder(parent, items) { items.forEach(function (el) { parent.appendChild(el); }); }

    // 1) Bảng 7 ngày: bấm tiêu đề ngày -> nhiều task hoàn thành lên đầu; bấm lại -> đảo chiều.
    var week = document.querySelector('table.week');
    if (week) {
        var body = week.tBodies[0];
        var buttons = week.querySelectorAll('.sortbtn');
        var cur = { col: null, dir: 1 };
        Array.prototype.forEach.call(buttons, function (btn) {
            btn.addEventListener('click', function () {
                var col = +btn.getAttribute('data-col');
                cur.dir = cur.col === col ? -cur.dir : 1;
                cur.col = col;
                var cell = function (attr) {
                    return function (tr) { var td = tr.cells[col]; return td ? +td.getAttribute(attr) || 0 : 0; };
                };
                var rows = Array.prototype.slice.call(body.rows);
                rows.sort(by([cell('data-done'), cell('data-tasks')], cur.dir));
                reorder(body, rows);
                Array.prototype.forEach.call(buttons, function (b) {
                    b.parentNode.setAttribute('aria-sort', b === btn ? (cur.dir === 1 ? 'descending' : 'ascending') : 'none');
                });
            });
        });
    }

    // 2) Danh sách chi tiết bên dưới: sắp theo số task hoặc số hoàn thành.
    var detail = document.getElementById('detail');
    var bar = document.querySelector('.sortbar');
    if (detail && bar) {
        bar.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-sort]');
            if (!btn) return;
            var num = function (name) { return function (el) { return +el.getAttribute(name) || 0; }; };
            var keys = btn.getAttribute('data-sort') === 'done'
                ? [num('data-done'), num('data-tasks'), num('data-hours')]
                : [num('data-tasks'), num('data-hours')];
            var items = Array.prototype.slice.call(detail.children);
            items.sort(by(keys, 1));
            reorder(detail, items);
            items.forEach(function (el, i) { el.querySelector('.rank').textContent = i + 1; });
            Array.prototype.forEach.call(bar.querySelectorAll('button'), function (b) { b.classList.toggle('on', b === btn); });
        });
    }
})();
</script>
@endpush
