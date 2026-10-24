<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Dữ liệu cố định (không dùng Faker) để seed được cả trên image prod, nơi package dev không được cài.
     * Chạy lại nhiều lần không tạo bản ghi trùng.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => 'Pipeline CI/CD đầu tiên đã chạy xanh',
                'summary' => 'Jenkins và GitHub Actions cùng lint, test và build image cho dự án iweb-devops.',
                'content' => 'Mỗi lần đẩy code lên GitHub, pipeline tự chạy Pint, PHPUnit rồi build image prod. Lỗi được phát hiện ngay từ đầu, không cần chờ tới lúc deploy.',
                'days_ago' => 1,
            ],
            [
                'title' => 'Ứng dụng được đóng gói bằng Docker',
                'summary' => 'Một image duy nhất chạy giống nhau trên máy dev, CI và server staging.',
                'content' => 'Image dùng nginx và php-fpm trong cùng một container, chạy bằng user thường và lắng nghe cổng 8080. Cấu hình được truyền qua biến môi trường, không nằm trong image.',
                'days_ago' => 2,
            ],
            [
                'title' => 'Staging chạy trên AWS EC2',
                'summary' => 'Server Ubuntu 24.04 kéo image từ ECR và chạy bằng Docker Compose.',
                'content' => 'Instance t3.small chạy app và MySQL. Cổng 80 mở ra internet, cổng SSH chỉ mở cho IP quản trị, cổng database không mở ra ngoài.',
                'days_ago' => 3,
            ],
            [
                'title' => 'Image được lưu trên Amazon ECR',
                'summary' => 'Mỗi bản build có một tag riêng để dễ quay lui khi cần.',
                'content' => 'EC2 dùng IAM role chỉ có quyền đọc ECR để kéo image, vì vậy không có access key nào được lưu trên server.',
                'days_ago' => 4,
            ],
            [
                'title' => 'Bí mật được tách khỏi mã nguồn',
                'summary' => 'APP_KEY và mật khẩu database nằm trong file .env.staging, không commit lên Git.',
                'content' => 'File mẫu .env.staging.example chỉ chứa tên biến. Giá trị thật được sinh ngẫu nhiên và chỉ tồn tại trên máy deploy và server.',
                'days_ago' => 5,
            ],
        ];

        foreach ($items as $item) {
            News::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'summary' => $item['summary'],
                    'content' => $item['content'],
                    'published_at' => now()->subDays($item['days_ago']),
                ],
            );
        }
    }
}
