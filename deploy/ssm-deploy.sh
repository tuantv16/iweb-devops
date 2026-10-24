#!/usr/bin/env bash
# Deploy lên staging qua AWS Systems Manager (SSM), không cần mở cổng 22.
#
#   bash deploy/ssm-deploy.sh <instance-id> <region> <image>
#
# Cần: AWS CLI và quyền ssm:SendCommand (trên instance + document AWS-RunShellScript) và
# ssm:GetCommandInvocation (xem deploy/jenkins-ssm-policy.json).
#
# Việc làm: gửi docker-compose.staging.yml, Caddyfile.staging và deploy/staging-deploy.sh lên server
# (nhúng dạng base64 vào lệnh SSM), rồi chạy staging-deploy.sh bằng user ubuntu; in log lại và trả mã
# thoát khác 0 nếu deploy lỗi.
set -euo pipefail

INSTANCE_ID="${1:?Thiếu instance id, vd i-0123456789abcdef0}"
REGION="${2:?Thiếu region, vd ap-southeast-1}"
IMAGE="${3:?Thiếu image đầy đủ, vd 123.dkr.ecr.ap-southeast-1.amazonaws.com/iweb-devops:develop-abc1234}"

cd "$(dirname "$0")/.."

b64() { base64 < "$1" | tr -d '\n'; }

payload="$(mktemp)"
trap 'rm -f "$payload"' EXIT

# SSM chạy bằng root, nên phải chuyển sang ubuntu: file compose, .env.staging, volume Docker đều của ubuntu.
cat > "$payload" <<EOF
{"commands":[
"set -eu",
"cd /home/ubuntu",
"echo $(b64 docker-compose.staging.yml) | base64 -d > docker-compose.staging.yml",
"echo $(b64 Caddyfile.staging) | base64 -d > Caddyfile.staging",
"echo $(b64 deploy/staging-deploy.sh) | base64 -d > staging-deploy.sh",
"chown ubuntu:ubuntu docker-compose.staging.yml Caddyfile.staging staging-deploy.sh",
"sudo -iu ubuntu bash -s -- '$IMAGE' < staging-deploy.sh"
]}
EOF

# Git Bash trên Windows cần đường dẫn kiểu Windows cho file://
payload_path="$payload"
if command -v cygpath > /dev/null 2>&1; then payload_path="$(cygpath -w "$payload")"; fi

cmd_id="$(aws ssm send-command \
  --region "$REGION" \
  --instance-ids "$INSTANCE_ID" \
  --document-name AWS-RunShellScript \
  --comment "iweb-devops deploy" \
  --timeout-seconds 900 \
  --parameters "file://$payload_path" \
  --query Command.CommandId --output text)"
echo "==> SSM command: $cmd_id"

# Tự đợi tối đa 15 phút (lệnh `aws ssm wait` chỉ đợi khoảng 100 giây là bỏ cuộc).
status=Pending
for _ in $(seq 1 180); do
  status="$(aws ssm get-command-invocation --region "$REGION" --command-id "$cmd_id" --instance-id "$INSTANCE_ID" \
    --query Status --output text 2> /dev/null || echo Pending)"
  case "$status" in
    Pending | InProgress | Delayed) sleep 5 ;;
    *) break ;;
  esac
done

echo "==> Kết quả trên server (stdout):"
aws ssm get-command-invocation --region "$REGION" --command-id "$cmd_id" --instance-id "$INSTANCE_ID" \
  --query StandardOutputContent --output text || true
echo "==> Kết quả trên server (stderr):" >&2
aws ssm get-command-invocation --region "$REGION" --command-id "$cmd_id" --instance-id "$INSTANCE_ID" \
  --query StandardErrorContent --output text >&2 || true

if [ "$status" != "Success" ]; then
  echo "!!! Deploy không thành công, trạng thái SSM: $status" >&2
  exit 1
fi
echo "==> Deploy xong: $IMAGE"
