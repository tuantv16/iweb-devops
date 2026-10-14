---
name: git-commit
description: Tạo commit git theo quy tắc của dự án (Conventional Commits, nhánh, không lộ secret). Dùng khi người dùng yêu cầu commit, tạo nhánh, hoặc push code.
---

# Quy tắc commit

## Định dạng message (Conventional Commits)

```
<type>(<scope>): <subject>

<body: giải thích VÌ SAO, không lặp lại diff>

Co-Authored-By: ...   (nếu hệ thống yêu cầu)
```

- **type**: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`.
- **scope** (tuỳ chọn): `docker`, `laravel`, `ci`, `db`, `deps`, `docs`...
- **subject**: tiếng Anh, thể mệnh lệnh ("add", không phải "added"), chữ thường, không dấu chấm cuối, **<= 72 ký tự**.
- **body**: ngắt dòng ~72 ký tự, giải thích lý do và tác động. Breaking change ghi `BREAKING CHANGE:` hoặc thêm `!` sau type.

Ví dụ:
```
fix(laravel): remove duplicated APP_KEY that broke encryption

Two base64 keys were concatenated on one line, so the cipher key length
was invalid and every request returned HTTP 500.
```

## Nhánh

- `main`: ổn định. `develop`: tích hợp, chạy CI. Nhánh làm việc: `feature/<mo-ta>`, `fix/<mo-ta>`, `chore/<mo-ta>`.
- **Không commit trực tiếp vào `main` / `develop`** (trừ commit khởi tạo repo). Làm trên nhánh riêng rồi mở PR vào `develop`.

## Trước khi commit

1. `git status` và `git diff` — xem đúng thứ sẽ commit.
2. Stage **theo file cụ thể**, tránh `git add -A` mù quáng khi chưa xem `git status`.
3. **Tuyệt đối không commit**: `.env`, `.env.local`, `.env.staging`, `.env.production`, key/mật khẩu/token, `vendor/`, `node_modules/`, file `*.sqlite`, log. Nếu thấy secret trong diff, dừng lại và báo người dùng.
4. Mỗi commit một thay đổi logic; không trộn refactor với fix.
5. Nếu có test/lint (`php artisan test`, `./vendor/bin/pint --test`), chạy trước khi commit.

## Sau khi commit

- **Chỉ push khi người dùng yêu cầu.** Không `--force`, không `--no-verify`, không amend commit đã push.
- Hook lỗi thì sửa nguyên nhân rồi tạo commit **mới**.
- Báo lại: hash, message, số file thay đổi.
