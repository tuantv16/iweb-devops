<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Gom danh sách issue Redmine thành số liệu theo từng thành viên. Thuần tính toán, không gọi mạng.
 */
class TaskDashboard
{
    /** Nhóm trạng thái hiển thị, theo thứ tự từ "chưa làm" đến "vướng". */
    public const GROUPS = [
        'new' => 'Chưa làm',
        'doing' => 'Đang làm',
        'review' => 'Review / Resolved',
        'blocked' => 'Vướng / Chờ phản hồi',
    ];

    private const UNASSIGNED = 'Chưa giao';

    /**
     * @param  array  $open  issue chưa đóng
     * @param  array  $closed  issue vừa đóng gần đây
     * @return array{totals: array, members: array}
     */
    public static function build(array $open, array $closed, CarbonInterface $today): array
    {
        $members = [];

        foreach ($open as $issue) {
            $name = self::memberName($issue);
            $members[$name] ??= self::emptyMember($name);

            $group = self::groupOf($issue['status']['name'] ?? '');
            $members[$name]['groups'][$group]++;
            $members[$name]['open']++;
            $members[$name]['ratio_sum'] += (int) ($issue['done_ratio'] ?? 0);

            $overdue = isset($issue['due_date']) && $issue['due_date'] < $today->toDateString();
            if ($overdue) {
                $members[$name]['overdue']++;
            }

            $members[$name]['tasks'][] = [
                'id' => $issue['id'],
                'subject' => $issue['subject'] ?? '',
                'status' => $issue['status']['name'] ?? '',
                'group' => $group,
                'done_ratio' => (int) ($issue['done_ratio'] ?? 0),
                'due_date' => $issue['due_date'] ?? null,
                'overdue' => $overdue,
            ];
        }

        foreach ($closed as $issue) {
            $name = self::memberName($issue);
            $members[$name] ??= self::emptyMember($name);
            $members[$name]['done_recent']++;
        }

        foreach ($members as &$m) {
            $m['avg_progress'] = $m['open'] > 0 ? (int) round($m['ratio_sum'] / $m['open']) : 0;
            usort($m['tasks'], fn ($a, $b) => [array_search($a['group'], array_keys(self::GROUPS)), $a['due_date'] ?? '9999', $a['id']]
                <=> [array_search($b['group'], array_keys(self::GROUPS)), $b['due_date'] ?? '9999', $b['id']]);
            unset($m['ratio_sum']);
        }
        unset($m);

        // Nhiều việc nhất lên đầu, "Chưa giao" luôn ở cuối.
        uasort($members, fn ($a, $b) => [$a['name'] === self::UNASSIGNED, -$a['open'], $a['name']]
            <=> [$b['name'] === self::UNASSIGNED, -$b['open'], $b['name']]);

        return [
            'totals' => self::totals($members),
            'members' => array_values($members),
        ];
    }

    /**
     * Thống kê "mỗi ngày mỗi người làm gì" từ log thời gian.
     *
     * @param  array  $entries  time_entries của Redmine trong khoảng ngày cần xem
     * @param  array  $issues  id => issue (để lấy tiêu đề, trạng thái, % tiến độ hiện tại)
     * @param  CarbonInterface  $date  ngày đang xem chi tiết
     * @param  string[]  $days  các ngày (Y-m-d) làm cột của bảng tổng quan, cũ đến mới
     * @return array{totals: array, members: array}
     */
    public static function daily(array $entries, array $issues, CarbonInterface $date, array $days): array
    {
        $selected = $date->toDateString();
        $members = [];

        foreach ($entries as $e) {
            $day = $e['spent_on'] ?? null;
            $issueId = $e['issue']['id'] ?? null;
            if (! in_array($day, $days, true)) {
                continue;
            }

            $name = isset($e['user']['name']) ? self::shortName($e['user']['name']) : self::UNASSIGNED;
            $members[$name] ??= ['name' => $name, 'by_day' => [], 'tasks' => [], 'hours' => 0.0];

            $cell = $members[$name]['by_day'][$day] ??= ['issues' => [], 'hours' => 0.0];
            $cell['hours'] += (float) $e['hours'];
            if ($issueId !== null) {
                $cell['issues'][$issueId] = self::isDone($issues[$issueId]['status']['name'] ?? '');
            }
            $members[$name]['by_day'][$day] = $cell;

            if ($day !== $selected || $issueId === null) {
                continue;
            }

            $issue = $issues[$issueId] ?? null;
            $task = $members[$name]['tasks'][$issueId] ?? [
                'id' => $issueId,
                'subject' => $issue['subject'] ?? '(không đọc được tiêu đề)',
                'status' => $issue['status']['name'] ?? '',
                'group' => self::groupOf($issue['status']['name'] ?? ''),
                'done' => self::isDone($issue['status']['name'] ?? ''),
                'done_ratio' => (int) ($issue['done_ratio'] ?? 0),
                'closed_today' => isset($issue['closed_on']) && str_starts_with($issue['closed_on'], $selected),
                'hours' => 0.0,
                'activities' => [],
                'notes' => [],
            ];
            $task['hours'] += (float) $e['hours'];
            if (! empty($e['activity']['name'])) {
                $task['activities'][$e['activity']['name']] = true;
            }
            if (! empty($e['comments'])) {
                $task['notes'][] = $e['comments'];
            }
            $members[$name]['tasks'][$issueId] = $task;
            $members[$name]['hours'] += (float) $e['hours'];
        }

        foreach ($members as &$m) {
            foreach ($m['by_day'] as &$cell) {
                $cell = [
                    'tasks' => count($cell['issues']),
                    'done' => count(array_filter($cell['issues'])),
                    'hours' => $cell['hours'],
                ];
            }
            unset($cell);

            foreach ($m['tasks'] as &$t) {
                $t['activities'] = array_keys($t['activities']);
            }
            unset($t);
            $m['tasks'] = array_values($m['tasks']);
            $m['done'] = count(array_filter(array_column($m['tasks'], 'done')));
        }
        unset($m);

        // Ngày đang xem có nhiều task nhất lên đầu, rồi đến nhiều giờ nhất, rồi theo tên.
        uasort($members, fn ($a, $b) => [-count($a['tasks']), -$a['hours'], $a['name']] <=> [-count($b['tasks']), -$b['hours'], $b['name']]);

        $active = array_filter($members, fn ($m) => $m['tasks'] !== []);

        return [
            'totals' => [
                'members' => count($active),
                'tasks' => array_sum(array_map(fn ($m) => count($m['tasks']), $active)),
                'done' => array_sum(array_column($active, 'done')),
                'hours' => array_sum(array_column($active, 'hours')),
            ],
            'members' => array_values($members),
        ];
    }

    /** Gom tên trạng thái (Anh/Nhật) về một trong 4 nhóm. */
    public static function groupOf(string $status): string
    {
        $s = mb_strtolower($status);

        return match (true) {
            self::has($s, ['feedback', 'pending', 'フィード', '保留']) => 'blocked',
            self::has($s, ['review', 'resolved', 'tested', 'testing', 'confirm', '対応済']) => 'review',
            self::has($s, ['progress', 'coding', 'investigat', '対応中', '製作', '調査']) => 'doing',
            default => 'new',
        };
    }

    /** Task coi là đã hoàn thành khi đang ở trạng thái Resolved hoặc Closed (kể cả tên tiếng Nhật; Cancelled không tính). */
    public static function isDone(string $status): bool
    {
        return self::has(mb_strtolower($status), ['resolved', 'closed']);
    }

    /** "NAM NGUYEN DUY (VJP.J3.G4 - VJP)" -> "NAM NGUYEN DUY" */
    public static function shortName(string $name): string
    {
        return trim(preg_replace('/\s*\(.*\)\s*$/u', '', $name) ?? $name);
    }

    private static function memberName(array $issue): string
    {
        $name = $issue['assigned_to']['name'] ?? null;

        return $name ? self::shortName($name) : self::UNASSIGNED;
    }

    private static function emptyMember(string $name): array
    {
        return [
            'name' => $name,
            'open' => 0,
            'overdue' => 0,
            'done_recent' => 0,
            'ratio_sum' => 0,
            'avg_progress' => 0,
            'groups' => array_fill_keys(array_keys(self::GROUPS), 0),
            'tasks' => [],
        ];
    }

    private static function totals(array $members): array
    {
        $totals = [
            'open' => 0,
            'overdue' => 0,
            'unassigned' => $members[self::UNASSIGNED]['open'] ?? 0,
            'done_recent' => 0,
            'groups' => array_fill_keys(array_keys(self::GROUPS), 0),
        ];

        foreach ($members as $m) {
            $totals['open'] += $m['open'];
            $totals['overdue'] += $m['overdue'];
            $totals['done_recent'] += $m['done_recent'];
            foreach ($m['groups'] as $g => $n) {
                $totals['groups'][$g] += $n;
            }
        }

        return $totals;
    }

    private static function has(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($haystack, $n)) {
                return true;
            }
        }

        return false;
    }
}
