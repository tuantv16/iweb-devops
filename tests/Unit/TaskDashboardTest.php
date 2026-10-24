<?php

namespace Tests\Unit;

use App\Services\TaskDashboard;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskDashboardTest extends TestCase
{
    #[DataProvider('statusGroups')]
    public function test_status_is_grouped(string $status, string $group): void
    {
        $this->assertSame($group, TaskDashboard::groupOf($status));
    }

    public static function statusGroups(): array
    {
        return [
            ['New', 'new'],
            ['Todo', 'new'],
            ['In-Progress', 'doing'],
            ['製作_中/Coding', 'doing'],
            ['In-Review', 'review'],
            ['Resolved', 'review'],
            ['Feedback', 'blocked'],
            ['Pending', 'blocked'],
            ['Trạng thái lạ', 'new'],
        ];
    }

    #[DataProvider('doneStatuses')]
    public function test_done_means_resolved_or_closed(string $status, bool $done): void
    {
        $this->assertSame($done, TaskDashboard::isDone($status));
    }

    public static function doneStatuses(): array
    {
        return [
            ['Resolved', true],
            ['Closed', true],
            ['完了/Closed', true],
            ['対応済/ Resolved', true],
            ['In-Progress', false],
            ['In-Review', false],
            ['Cancelled', false],
            ['New', false],
        ];
    }

    public function test_short_name_drops_the_team_suffix(): void
    {
        $this->assertSame('NAM NGUYEN DUY', TaskDashboard::shortName('NAM NGUYEN DUY (VJP.J3.G4 - VJP)'));
        $this->assertSame('Tuan', TaskDashboard::shortName('Tuan'));
    }

    public function test_build_counts_per_member_and_totals(): void
    {
        $today = Carbon::parse('2026-10-24');
        $open = [
            $this->issue(1, 'In-Progress', 'An (T1)', 50, '2026-10-20'),
            $this->issue(2, 'New', 'An (T1)', 0, '2026-10-30'),
            $this->issue(3, 'Feedback', 'Binh (T1)', 100, null),
            $this->issue(4, 'New', null, 0, null),
        ];
        $closed = [$this->issue(5, 'Closed', 'An (T1)', 100, null)];

        $r = TaskDashboard::build($open, $closed, $today);

        $this->assertSame(4, $r['totals']['open']);
        $this->assertSame(1, $r['totals']['overdue']);
        $this->assertSame(1, $r['totals']['unassigned']);
        $this->assertSame(1, $r['totals']['done_recent']);

        // An có 2 task mở nên đứng đầu, "Chưa giao" luôn cuối.
        $this->assertSame(['An', 'Binh', 'Chưa giao'], array_column($r['members'], 'name'));

        $an = $r['members'][0];
        $this->assertSame(2, $an['open']);
        $this->assertSame(1, $an['overdue']);
        $this->assertSame(1, $an['done_recent']);
        $this->assertSame(25, $an['avg_progress']);
        $this->assertSame(1, $an['groups']['doing']);
        $this->assertSame(1, $an['groups']['new']);
        // Sắp theo nhóm: "Chưa làm" (#2) trước "Đang làm" (#1).
        $this->assertSame([2, 1], array_column($an['tasks'], 'id'));
    }

    public function test_member_with_only_closed_tasks_has_no_division_by_zero(): void
    {
        $r = TaskDashboard::build([], [$this->issue(9, 'Closed', 'Chi (T1)', 100, null)], Carbon::parse('2026-10-24'));

        $this->assertSame(0, $r['members'][0]['open']);
        $this->assertSame(0, $r['members'][0]['avg_progress']);
        $this->assertSame(1, $r['members'][0]['done_recent']);
    }

    public function test_daily_groups_time_entries_per_member_and_day(): void
    {
        $date = Carbon::parse('2026-10-23');
        $days = ['2026-10-22', '2026-10-23'];
        $entry = fn (int $issue, string $user, string $day, float $h, string $note) => [
            'spent_on' => $day, 'hours' => $h, 'comments' => $note,
            'issue' => ['id' => $issue], 'user' => ['name' => $user], 'activity' => ['name' => 'Coding'],
        ];
        $entries = [
            $entry(1, 'An (T1)', '2026-10-23', 2.0, 'làm màn A'),
            $entry(1, 'An (T1)', '2026-10-23', 1.5, 'sửa lỗi màn A'),
            $entry(2, 'An (T1)', '2026-10-23', 3.0, 'màn B'),
            $entry(3, 'An (T1)', '2026-10-22', 4.0, 'hôm qua'),
            $entry(2, 'Binh (T1)', '2026-10-22', 8.0, 'chỉ làm hôm qua'),
            $entry(9, 'Chi (T1)', '2026-10-01', 8.0, 'ngoài khoảng ngày'),
        ];
        $issues = [
            1 => ['id' => 1, 'subject' => 'Màn A', 'status' => ['name' => 'In-Progress'], 'done_ratio' => 60],
            2 => ['id' => 2, 'subject' => 'Màn B', 'status' => ['name' => 'Closed'], 'done_ratio' => 100, 'closed_on' => '2026-10-23T09:00:00Z'],
        ];

        $r = TaskDashboard::daily($entries, $issues, $date, $days);

        // Binh không làm gì ngày đang xem nhưng vẫn có ở bảng 7 ngày; Chi nằm ngoài khoảng nên bị bỏ.
        $this->assertSame(['An', 'Binh'], array_column($r['members'], 'name'));
        $this->assertSame(1, $r['totals']['members']);
        $this->assertSame(2, $r['totals']['tasks']);
        $this->assertSame(1, $r['totals']['done']);
        $this->assertEqualsWithDelta(6.5, $r['totals']['hours'], 0.001);

        $an = $r['members'][0];
        $this->assertSame(['tasks' => 2, 'done' => 1, 'hours' => 6.5], $an['by_day']['2026-10-23']);
        $this->assertSame(['tasks' => 1, 'done' => 0, 'hours' => 4.0], $an['by_day']['2026-10-22']);
        $this->assertSame(1, $an['done']);

        [$t1, $t2] = $an['tasks'];
        $this->assertSame(1, $t1['id']);
        $this->assertEqualsWithDelta(3.5, $t1['hours'], 0.001);
        $this->assertSame(['làm màn A', 'sửa lỗi màn A'], $t1['notes']);
        $this->assertSame(60, $t1['done_ratio']);
        $this->assertFalse($t1['closed_today']);
        $this->assertFalse($t1['done']);
        $this->assertTrue($t2['closed_today']);
        $this->assertTrue($t2['done']);

        $this->assertSame([], $r['members'][1]['tasks']);
    }

    private function issue(int $id, string $status, ?string $assignee, int $ratio, ?string $due): array
    {
        return array_filter([
            'id' => $id,
            'subject' => "Task $id",
            'status' => ['name' => $status],
            'assigned_to' => $assignee ? ['name' => $assignee] : null,
            'done_ratio' => $ratio,
            'due_date' => $due,
        ], fn ($v) => $v !== null);
    }
}
