# iweb-devops — Laravel News · Docker · AWS ECS · Jenkins

Dự án học DevOps: web tin tức Laravel đơn giản (trang `welcome.blade.php` đọc tin từ MySQL),
đóng gói Docker, chạy trên AWS ECS (EC2), CI bằng Jenkins.

## Lộ trình

| Bước | Nội dung | Trạng thái |
|------|----------|-----------|
| 1 | Khởi tạo Laravel + trang tin tức | Xong |
| 2 | Docker hoá (Dev/Local) | Đã viết file, chờ chạy thử |
| 3 | Test + Lint | — |
| 4 | Cấu hình Staging + build image | — |
| 5 | Hạ tầng AWS (ECR + ECS trên EC2) | — |
| 6 | Jenkins CI (Test + Lint) | — |
| 7 | Mở rộng CD (build/push ECR + deploy ECS) | — |

---

## Bước 1 — Khởi tạo Laravel + trang tin tức

### Yêu cầu
PHP >= 8.2, Composer 2 (đã kiểm tra: PHP 8.2.12, Composer 2.10).

### Các lệnh đã chạy
```bash
# 1. Tạo project Laravel ngay tại thư mục repo (thư mục phải rỗng)
composer create-project laravel/laravel . --no-interaction --prefer-dist

# 2. Sinh model + migration + factory, seeder, controller
php artisan make:model News -mf
php artisan make:seeder NewsSeeder
php artisan make:controller HomeController
```

### Các file đã tạo / sửa
| File | Vai trò |
|------|---------|
| `database/migrations/*_create_news_table.php` | Bảng `news`: `title`, `summary`, `content`, `published_at` (index) |
| `app/Models/News.php` | Model, `$table = 'news'`, scope `published()` (chỉ lấy tin đã đến giờ đăng) |
| `database/factories/NewsFactory.php` | Dữ liệu giả; state `unpublished()` |
| `database/seeders/NewsSeeder.php` | Tạo 8 tin mẫu; `DatabaseSeeder` gọi seeder này |
| `app/Http/Controllers/HomeController.php` | Lấy 10 tin mới nhất, trả view `welcome` |
| `routes/web.php` | `GET /` → `HomeController@index` |
| `resources/views/welcome.blade.php` | Giao diện danh sách tin (hỗ trợ dark mode), hiển thị `APP_ENV` |

### Chạy thử local (chưa cần Docker)
```bash
php artisan migrate:fresh --seed
php artisan serve          # mở http://127.0.0.1:8000
```
Mặc định `.env` dùng SQLite (`database/database.sqlite`) cho tiện. Ở Bước 2, DB chuyển sang
container MySQL qua biến `DB_*` trong `.env.local` / `.env.staging`.

### Kiểm tra
- `GET /` trả HTTP 200 và có 8 thẻ `<article>` (đã kiểm tra bằng curl).
- Tên bảng `news` đúng (Laravel coi "news" là danh từ không đếm được, nhưng vẫn đặt `$table` tường minh để tránh nhầm).

---

## Bước 2 — Docker hoá (Dev/Local)

Hai container riêng biệt:

| Service | Image | Vai trò | Cổng trên máy |
|---------|-------|---------|---------------|
| `app` | build từ `docker/php/Dockerfile` (`serversideup/php:8.2-fpm-nginx`) | nginx + php-fpm chứa source Laravel | `8080` |
| `db` | `mysql:8.0` | MySQL, dữ liệu lưu ở volume `db_data` | `3307` (để dùng DBeaver/HeidiSQL...) |

### File liên quan
| File | Vai trò |
|------|---------|
| `docker/php/Dockerfile` | Multi-stage: `base` → `dev` (có package dev) / `prod` (`--no-dev`, dùng cho Staging) |
| `docker-compose.yml` | Phần chung: `app`, `db`, network, volume, healthcheck MySQL |
| `docker-compose.override.yml` | Riêng Dev/Local, tự nạp: target `dev`, bind-mount code, `env_file`, mở cổng DB |
| `.env.local.example` | Mẫu biến môi trường local (copy thành `.env.local`, file này bị gitignore) |
| `.dockerignore` | Không đưa `.env*`, `vendor`, `.git`, log vào image |

### Các lệnh chạy
```powershell
# 0. Bật Docker Desktop, đợi báo "running", rồi kiểm tra
docker version

# 1. Tạo file env local
copy .env.local.example .env.local

# 2. Sinh APP_KEY, copy kết quả (base64:...) dán vào dòng APP_KEY= trong .env.local
docker compose run --rm app php artisan key:generate --show

# 3. Build image + chạy 2 container (lần đầu lâu vì kéo image)
docker compose up -d --build

# 4. Kiểm tra: cả hai Up, db phải "healthy"
docker compose ps

# 5. Tạo bảng + dữ liệu mẫu
docker compose exec app php artisan migrate --seed

# 6. Mở http://localhost:8080
```

### Lỗi thường gặp
| Lỗi | Nguyên nhân | Cách xử lý |
|-----|-------------|-----------|
| `MissingAppKeyException: No application encryption key has been specified` | `APP_KEY` trong `.env.local` còn trống (hoặc sửa file sau khi container đã tạo) | Điền `APP_KEY` ở bước 2, rồi tạo lại container: `docker compose up -d --force-recreate app` |

> `docker compose restart` **không** đọc lại `.env.local`. Mỗi lần sửa file này phải chạy `docker compose up -d --force-recreate app`.

### Lệnh hay dùng
```powershell
docker compose logs -f app          # xem log app
docker compose exec app sh          # vào shell container app
docker compose exec db mysql -uiweb -piweb_local_pw iweb_news -e "select id,title from news;"
docker compose down                 # dừng, giữ dữ liệu DB
docker compose down -v              # dừng và XOÁ dữ liệu DB + vendor volume
docker compose up -d --build -V     # build lại và tạo lại volume vendor (sau khi đổi composer.json)
```

### Lưu ý
- Biến môi trường trong container ghi đè `.env` (sqlite) của Laravel, nên không cần sửa `.env`.
- Nếu cổng 8080 / 3307 trên máy đã bị chiếm, đổi số bên trái trong `ports:`.
- MySQL chỉ đọc `MYSQL_*` lần đầu tạo volume. Đổi mật khẩu sau đó cần `docker compose down -v`.
