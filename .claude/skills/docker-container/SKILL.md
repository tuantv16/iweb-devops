---
name: docker-container
description: Tạo, chạy, kiểm tra và dọn dẹp container Docker cho dự án Laravel (app nginx+php-fpm và MySQL) bằng Docker Compose. Dùng khi người dùng muốn dựng/chạy/xoá container, sửa Dockerfile hoặc docker-compose.
---

# Docker container cho dự án Laravel

## Kiến trúc hiện tại

| Service | Image | Cổng máy | Ghi chú |
|---------|-------|----------|---------|
| `app` | build từ `docker/php/Dockerfile` (`serversideup/php:8.2-fpm-nginx`) | 8080 | target `dev` (local) / `prod` (staging, production) |
| `db` | `mysql:8.0` | 3307 | volume `db_data`, có healthcheck |

File: `docker-compose.yml` (chung), `docker-compose.override.yml` (dev, tự nạp), `.env.local` (không commit).

## Dựng container từ đầu

```powershell
copy .env.local.example .env.local            # rồi điền APP_KEY
docker compose up -d --build
docker compose ps                             # db phải "healthy"
docker compose exec app php artisan migrate --seed
```

## Quy tắc

- **Sửa `.env.local` xong phải** `docker compose up -d --force-recreate app` — `restart` không đọc lại env.
- Biến trong container ghi đè `.env`; `APP_KEY` trong `.env.local` phải có giá trị hợp lệ (một key `base64:...`).
- Đổi `composer.json` thì build lại: `docker compose up -d --build -V`.
- Lỗi cache key/config: `docker compose exec app php artisan config:clear`.
- Đặt tên container/service rõ ràng, tránh trùng cổng với container khác (kiểm tra `docker ps` trước khi mở cổng mới).
- `.dockerignore` phải chặn `.env*` (trừ `.env.example`), `vendor`, `.git`.

## Dọn dẹp (thao tác phá huỷ — xác nhận với người dùng trước)

```powershell
docker compose down            # dừng, giữ dữ liệu DB
docker compose down -v         # XOÁ cả volume (mất dữ liệu DB)
docker rm -f <ten-container>   # xoá container lẻ
```

Trước khi xoá: chạy `docker ps -a`, liệt kê đúng container sẽ bị xoá, và **chỉ xoá container thuộc dự án này** (project `iweb-devops`). Không dùng `docker rm -f $(docker ps -aq)` hay `system prune -a` nếu chưa được người dùng cho phép rõ ràng.

## Chẩn đoán nhanh

```powershell
docker compose logs -f app
docker compose exec app sh
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080
```
