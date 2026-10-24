<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Đọc dữ liệu từ Redmine qua REST API (chỉ đọc). Cấu hình ở config/services.php -> 'redmine'.
 */
class RedmineClient
{
    private const PAGE_SIZE = 100;

    // Chặn vòng lặp vô hạn nếu server trả total_count bất thường.
    private const MAX_ITEMS = 5000;

    // Số issue_id gửi trong một request (danh sách id nằm trên URL nên không để quá dài).
    private const IDS_PER_REQUEST = 50;

    public function isConfigured(): bool
    {
        return config('services.redmine.url') && config('services.redmine.key') && config('services.redmine.project');
    }

    /** Task chưa đóng (status do Redmine quyết định qua cờ is_closed). */
    public function openIssues(): array
    {
        return $this->paginate('/issues.json', 'issues', ['status_id' => 'open']);
    }

    /** Task đã đóng kể từ ngày $since. */
    public function closedSince(CarbonInterface $since): array
    {
        return $this->paginate('/issues.json', 'issues', ['status_id' => 'closed', 'closed_on' => '>='.$since->toDateString()]);
    }

    /** Log thời gian (ai làm task nào, bao lâu, làm gì) trong khoảng ngày [$from, $to]. */
    public function timeEntries(CarbonInterface $from, CarbonInterface $to): array
    {
        return $this->paginate('/time_entries.json', 'time_entries', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * Lấy thông tin các issue theo id (kể cả đã đóng), trả về mảng id => issue.
     *
     * @param  int[]  $ids
     */
    public function issuesByIds(array $ids): array
    {
        $found = [];

        foreach (array_chunk(array_values(array_unique($ids)), self::IDS_PER_REQUEST) as $chunk) {
            foreach ($this->paginate('/issues.json', 'issues', ['issue_id' => implode(',', $chunk), 'status_id' => '*'], withProject: false) as $issue) {
                $found[$issue['id']] = $issue;
            }
        }

        return $found;
    }

    public function issueUrl(int $id): string
    {
        return rtrim((string) config('services.redmine.url'), '/').'/issues/'.$id;
    }

    /**
     * @throws RuntimeException khi chưa cấu hình, hoặc Redmine không trả lời / từ chối
     */
    private function paginate(string $path, string $key, array $filters, bool $withProject = true): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình REDMINE_URL / REDMINE_API_KEY / REDMINE_PROJECT.');
        }

        if ($withProject) {
            $filters['project_id'] = config('services.redmine.project');
        }

        $items = [];
        $offset = 0;

        try {
            do {
                $page = Http::baseUrl(rtrim((string) config('services.redmine.url'), '/'))
                    ->withHeaders(['X-Redmine-API-Key' => config('services.redmine.key')])
                    ->acceptJson()
                    ->timeout(20)
                    ->retry(2, 300, throw: false)
                    ->get($path, $filters + ['limit' => self::PAGE_SIZE, 'offset' => $offset])
                    ->throw()
                    ->json();

                $items = array_merge($items, $page[$key] ?? []);
                $offset += self::PAGE_SIZE;
                $total = (int) ($page['total_count'] ?? 0);
            } while ($offset < $total && $offset < self::MAX_ITEMS);
        } catch (ConnectionException|RequestException $e) {
            // Không đưa $e->getMessage() ra ngoài vì URL/phản hồi có thể chứa thông tin nhạy cảm.
            throw new RuntimeException('Không gọi được Redmine: '.class_basename($e).'.', previous: $e);
        }

        return $items;
    }
}
