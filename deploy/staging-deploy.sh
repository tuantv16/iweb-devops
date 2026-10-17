#!/usr/bin/env bash
# Chạy TRÊN server staging (Jenkins gọi qua SSH):  bash -s -- <image> < deploy/staging-deploy.sh
#
# Việc làm: kéo image mới từ ECR -> đổi APP_IMAGE -> up -d -> migrate -> kiểm tra web.
# Nếu bản mới không lên được thì trả APP_IMAGE về bản cũ (migration đã chạy KHÔNG được rollback).
#
# Toàn bộ logic nằm trong hàm main và được gọi với stdin = /dev/null: script đi vào qua stdin của
# `bash -s`, nếu không làm vậy thì `docker compose exec` sẽ đọc mất phần script phía sau.
set -euo pipefail

main() {
  local IMAGE="${1:?Thiếu tham số: image đầy đủ, vd 123.dkr.ecr.ap-southeast-1.amazonaws.com/iweb-devops:develop-abc1234}"
  cd "$HOME"

  # Chỉ một lần deploy chạy tại một thời điểm (tránh hai build giẫm lên nhau trên cùng container).
  exec 9> /tmp/iweb-staging-deploy.lock
  flock -w 600 9 || { echo "!!! Đang có lần deploy khác chạy quá 10 phút" >&2; return 1; }

  # APP_IMAGE nằm ở file riêng, KHÔNG nằm trong .env.staging: service db đọc .env.staging
  # (env_file), nếu file đó đổi mỗi lần deploy thì Compose sẽ tạo lại container db.
  local IMAGE_FILE=".app-image.env"
  local COMPOSE="docker compose -f docker-compose.staging.yml --env-file .env.staging --env-file $IMAGE_FILE"

  # Chuyển từ cách cũ (APP_IMAGE trong .env.staging) sang file riêng, chỉ làm một lần.
  if [ ! -f "$IMAGE_FILE" ]; then
    grep '^APP_IMAGE=' .env.staging > "$IMAGE_FILE" || echo "APP_IMAGE=$IMAGE" > "$IMAGE_FILE"
  fi
  sed -i '/^APP_IMAGE=/d' .env.staging

  local REGISTRY="${IMAGE%%/*}"
  local REGION
  REGION="$(echo "$REGISTRY" | cut -d. -f4)"
  local PREV_IMAGE
  PREV_IMAGE="$(grep '^APP_IMAGE=' "$IMAGE_FILE" | cut -d= -f2- || true)"

  set_image() {
    echo "APP_IMAGE=$1" > "$IMAGE_FILE"
  }

  wait_for_web() {
    for _ in $(seq 1 30); do
      if curl -fsS -o /dev/null http://localhost/; then return 0; fi
      sleep 3
    done
    return 1
  }

  echo "==> Đăng nhập ECR ($REGION) bằng IAM role của EC2"
  aws ecr get-login-password --region "$REGION" | docker login --username AWS --password-stdin "$REGISTRY"

  echo "==> Image hiện tại: ${PREV_IMAGE:-<chưa có>}"
  echo "==> Image mới:      $IMAGE"

  # Pull trước khi đổi gì: tag sai/không tồn tại thì dừng ở đây, server vẫn chạy bản cũ nguyên vẹn.
  if ! APP_IMAGE="$IMAGE" $COMPOSE pull app; then
    echo "!!! Không kéo được image $IMAGE, giữ nguyên bản đang chạy" >&2
    return 1
  fi

  set_image "$IMAGE"
  local up_ok=0 migrated=0
  if $COMPOSE up -d; then
    up_ok=1
    echo "==> Migrate database"
    for _ in $(seq 1 20); do
      if $COMPOSE exec -T app php artisan migrate --force; then migrated=1; break; fi
      sleep 3
    done
  fi

  if [ "$up_ok" = 1 ] && [ "$migrated" = 1 ] && wait_for_web; then
    echo "==> Web OK với image mới"
    docker image prune -af --filter "until=168h" > /dev/null || true
    return 0
  fi

  echo "!!! Image mới lỗi, rollback về: ${PREV_IMAGE:-<không có bản cũ>}" >&2
  $COMPOSE logs --tail 40 app >&2 || true
  if [ -n "$PREV_IMAGE" ]; then
    set_image "$PREV_IMAGE"
    $COMPOSE up -d
  fi
  return 1
}

main "$@" < /dev/null
