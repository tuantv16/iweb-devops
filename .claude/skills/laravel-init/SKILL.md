---
name: laravel-init
description: Khởi tạo một dự án Laravel mới (repo mới) với cấu trúc, .env và git chuẩn của dự án iweb-devops. Dùng khi người dùng muốn "tạo dự án Laravel mới", "init repo Laravel", "khởi tạo project".
---

# Khởi tạo dự án Laravel mới

## Quy trình

1. **Xác nhận đầu vào** (hỏi nếu thiếu): tên dự án/thư mục, phiên bản PHP, DB dùng (mặc định MySQL 8.0 qua Docker).
2. **Kiểm tra môi trường**: `php -v` (>= 8.2), `composer -V` (Composer 2). Thiếu thì báo người dùng, không tự cài.
3. **Thư mục đích phải rỗng** (hoặc chưa tồn tại). Nếu có file, dừng lại và hỏi — không ghi đè.
4. **Tạo project**:
   ```bash
   composer create-project laravel/laravel <ten-thu-muc> --no-interaction --prefer-dist
   ```
5. **Cấu hình env**:
   - Giữ `.env.example` làm mẫu (không chứa secret, `APP_KEY=` để trống).
   - Tạo `.env.local.example` cho Docker (host DB = tên service `db`), xem skill `docker-container`.
   - `APP_KEY` chỉ có đúng **một** key: sinh bằng `php artisan key:generate --show` rồi dán vào, **không** chạy `key:generate` lặp lại trên file đã có key.
6. **Git**:
   - `git init -b main`, rồi `git branch develop main`.
   - Đảm bảo `.gitignore` chặn: `.env`, `.env.local`, `.env.staging`, `.env.production`, `/vendor`, `/node_modules`, `*.sqlite`.
   - Commit đầu tiên theo skill `git-commit`: `chore: initial commit`.
7. **Kiểm tra**: `php artisan migrate --seed` rồi `php artisan serve`, `GET /` phải trả 200.

## Quy ước

- Nhánh: `main` (ổn định) ← `develop` (tích hợp, chạy CI) ← `feature/*`.
- Tên bảng/model đặt tường minh khi danh từ không đếm được (vd. `news`).
- Không commit `vendor/`, `.env*` thật, file DB.

## Báo cáo cuối

Liệt kê: đường dẫn dự án, các file đã tạo, lệnh chạy thử, và việc còn lại (remote, CI).
