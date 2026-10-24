@extends('layouts.site')

@section('title', config('app.name').' - Tasks')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tasks.css') }}?v={{ filemtime(public_path('css/tasks.css')) }}">
@endpush

@section('content')
<div class="tasks">
    <div class="head">
        <h1><span class="grad">Tasks</span> · {{ config('services.redmine.project') }}</h1>
        @isset($fetched_at)
            <span class="hint">Cập nhật {{ $fetched_at->timezone(config('services.redmine.timezone'))->format('H:i d/m') }} · <a href="{{ route('tasks', ['refresh' => 1]) }}">Làm mới</a></span>
        @endisset
    </div>

    @include('partials.task-tabs')

    @isset($error)
        <div class="err" role="alert">{{ $error }}</div>
    @else
        <div class="sums">
            <div class="sum"><b>{{ $totals['open'] }}</b><span>Task đang mở</span></div>
            <div class="sum bad"><b>{{ $totals['overdue'] }}</b><span>Quá hạn</span></div>
            <div class="sum"><b>{{ $totals['unassigned'] }}</b><span>Chưa giao</span></div>
            <div class="sum good"><b>{{ $totals['done_recent'] }}</b><span>Hoàn thành {{ $recentDays }} ngày qua</span></div>
        </div>

        <div class="legend">
            @foreach ($groups as $key => $label)
                <span><i class="g-{{ $key }}"></i>{{ $label }} ({{ $totals['groups'][$key] }})</span>
            @endforeach
        </div>

        @forelse ($members as $m)
            <details class="member">
                <summary>
                    <div>
                        <div class="name">{{ $m['name'] }}</div>
                        <div class="meta">Tiến độ TB {{ $m['avg_progress'] }}% · xong {{ $recentDays }} ngày: {{ $m['done_recent'] }}</div>
                    </div>
                    <div class="bar" role="img" aria-label="{{ $m['name'] }}: @foreach ($groups as $key => $label){{ $label }} {{ $m['groups'][$key] }}{{ $loop->last ? '' : ', ' }}@endforeach">
                        @foreach ($groups as $key => $label)
                            @if ($m['groups'][$key] > 0)
                                <span class="g-{{ $key }}" style="width: {{ $m['groups'][$key] / max($m['open'], 1) * 100 }}%" title="{{ $label }}: {{ $m['groups'][$key] }}"></span>
                            @endif
                        @endforeach
                    </div>
                    <div class="nums">
                        <b>{{ $m['open'] }}</b> mở
                        @if ($m['overdue'] > 0)
                            · <span class="late">{{ $m['overdue'] }} quá hạn</span>
                        @endif
                    </div>
                </summary>
                @if ($m['tasks'])
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Tiêu đề</th><th>Trạng thái</th><th>Tiến độ</th><th>Hạn</th></tr></thead>
                            <tbody>
                            @foreach ($m['tasks'] as $t)
                                <tr>
                                    <td class="id"><a href="{{ $redmine->issueUrl($t['id']) }}" target="_blank" rel="noopener noreferrer">#{{ $t['id'] }}</a></td>
                                    <td>{{ $t['subject'] }}</td>
                                    <td class="tag"><i class="g-{{ $t['group'] }}"></i>{{ $t['status'] }}</td>
                                    <td class="pct">{{ $t['done_ratio'] }}%</td>
                                    <td class="due {{ $t['overdue'] ? 'late' : '' }}">{{ $t['due_date'] ? \Illuminate\Support\Carbon::parse($t['due_date'])->format('d/m/Y') : '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </details>
        @empty
            <p class="hint">Không có task nào.</p>
        @endforelse
    @endisset
</div>
@endsection
