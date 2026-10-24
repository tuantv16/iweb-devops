<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskDashboardPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.redmine.url' => 'https://redmine.example.test',
            'services.redmine.key' => 'test-key',
            'services.redmine.project' => 'demo',
        ]);
    }

    public function test_dashboard_shows_members_and_status_groups(): void
    {
        Http::fake([
            'redmine.example.test/issues.json*status_id=open*' => Http::response([
                'total_count' => 2,
                'issues' => [
                    ['id' => 11, 'subject' => 'Làm màn hình A', 'status' => ['name' => 'In-Progress'],
                        'assigned_to' => ['name' => 'AN NGUYEN (T1 - VJP)'], 'done_ratio' => 40, 'due_date' => '2000-01-01'],
                    ['id' => 12, 'subject' => 'Sửa lỗi B', 'status' => ['name' => 'Feedback'],
                        'assigned_to' => ['name' => 'BINH TRAN (T1 - VJP)'], 'done_ratio' => 90],
                ],
            ]),
            'redmine.example.test/issues.json*status_id=closed*' => Http::response(['total_count' => 0, 'issues' => []]),
        ]);

        $this->get('/tasks')
            ->assertOk()
            ->assertSee('AN NGUYEN')
            ->assertSee('BINH TRAN')
            ->assertDontSee('T1 - VJP')
            ->assertSee('Làm màn hình A')
            ->assertSee('https://redmine.example.test/issues/11', false);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Redmine-API-Key', 'test-key'));
    }

    public function test_daily_tab_shows_what_each_member_did_that_day(): void
    {
        Http::fake([
            'redmine.example.test/time_entries.json*' => Http::response([
                'total_count' => 2,
                'time_entries' => [
                    ['spent_on' => '2026-10-23', 'hours' => 2.5, 'comments' => 'Đối chiếu mapping', 'issue' => ['id' => 21],
                        'user' => ['name' => 'AN NGUYEN (T1 - VJP)'], 'activity' => ['name' => 'Correct code']],
                    ['spent_on' => '2026-10-22', 'hours' => 4, 'comments' => 'hôm trước', 'issue' => ['id' => 22],
                        'user' => ['name' => 'AN NGUYEN (T1 - VJP)'], 'activity' => ['name' => 'Coding']],
                ],
            ]),
            'redmine.example.test/issues.json*' => Http::response([
                'total_count' => 1,
                'issues' => [['id' => 21, 'subject' => 'Migrate dữ liệu cửa hàng', 'status' => ['name' => 'In-Progress'], 'done_ratio' => 70]],
            ]),
        ]);

        $this->get('/tasks/daily?date=2026-10-23')
            ->assertOk()
            ->assertSee('Task mỗi ngày')
            ->assertSee('AN NGUYEN')
            ->assertSee('Migrate dữ liệu cửa hàng')
            ->assertSee('Đối chiếu mapping')
            ->assertSee('70%')
            ->assertSee('0 xong')
            ->assertSee('2.5h')
            ->assertDontSee('hôm trước');
    }

    public function test_daily_tab_rejects_a_malformed_date(): void
    {
        $this->get('/tasks/daily?date=hom-nay')->assertSessionHasErrors('date');
    }

    public function test_dashboard_shows_error_instead_of_crashing_when_redmine_is_down(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        $this->get('/tasks')->assertOk()->assertSee('Không gọi được Redmine');
    }

    public function test_dashboard_explains_missing_configuration(): void
    {
        config(['services.redmine.key' => null]);

        $this->get('/tasks')->assertOk()->assertSee('Chưa cấu hình');
    }
}
