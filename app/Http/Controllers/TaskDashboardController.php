<?php

namespace App\Http\Controllers;

use App\Services\RedmineClient;
use App\Services\TaskDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class TaskDashboardController extends Controller
{
    // Số ngày gần nhất tính là "đã hoàn thành gần đây".
    private const RECENT_DAYS = 7;

    // Số ngày hiển thị ở bảng tổng quan của tab "Task mỗi ngày" (kết thúc ở ngày đang chọn).
    private const DAILY_SPAN = 7;

    // Tăng số này mỗi khi đổi cấu trúc dữ liệu trả về của TaskDashboard, để cache cũ không gây lỗi sau khi deploy.
    private const CACHE_VERSION = 'v2';

    // Cache để không gọi Redmine mỗi lần F5 (khoảng 4-5 request); nút "Làm mới" bỏ qua cache.
    private const CACHE_SECONDS = 300;

    public function index(Request $request, RedmineClient $redmine): View
    {
        if ($request->boolean('refresh')) {
            Cache::forget('tasks.dashboard.'.self::CACHE_VERSION);
        }

        try {
            $data = Cache::remember('tasks.dashboard.'.self::CACHE_VERSION, self::CACHE_SECONDS, function () use ($redmine) {
                $today = now(config('services.redmine.timezone'));

                return TaskDashboard::build(
                    $redmine->openIssues(),
                    $redmine->closedSince($today->copy()->subDays(self::RECENT_DAYS)),
                    $today,
                ) + ['fetched_at' => $today];
            });
        } catch (RuntimeException $e) {
            return view('tasks', ['error' => $e->getMessage()]);
        }

        return view('tasks', $data + [
            'groups' => TaskDashboard::GROUPS,
            'recentDays' => self::RECENT_DAYS,
            'redmine' => $redmine,
        ]);
    }

    /** Tab "Task mỗi ngày": mỗi người làm bao nhiêu task trong ngày, task gì, tiến độ ra sao. */
    public function daily(Request $request, RedmineClient $redmine): View
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $today = Carbon::today(config('services.redmine.timezone'));
        $date = $request->filled('date') ? Carbon::createFromFormat('Y-m-d', $request->query('date'), config('services.redmine.timezone'))->startOfDay() : $today;

        $days = [];
        for ($i = self::DAILY_SPAN - 1; $i >= 0; $i--) {
            $days[] = $date->copy()->subDays($i)->toDateString();
        }

        $key = 'tasks.daily.'.self::CACHE_VERSION.'.'.$date->toDateString();
        if ($request->boolean('refresh')) {
            Cache::forget($key);
        }

        try {
            $data = Cache::remember($key, self::CACHE_SECONDS, function () use ($redmine, $date, $days) {
                $entries = $redmine->timeEntries(Carbon::parse($days[0]), $date);
                $ids = collect($entries)->pluck('issue.id')->filter()->unique()->all();

                return TaskDashboard::daily($entries, $redmine->issuesByIds($ids), $date, $days) + ['fetched_at' => now()];
            });
        } catch (RuntimeException $e) {
            return view('tasks-daily', ['error' => $e->getMessage(), 'date' => $date, 'today' => $today]);
        }

        return view('tasks-daily', $data + [
            'date' => $date,
            'today' => $today,
            'days' => $days,
            'redmine' => $redmine,
            'groups' => TaskDashboard::GROUPS,
        ]);
    }
}
