# ECR (Elastic Container Registry)

## Hiểu nhanh
Giống **Docker Hub riêng của bạn trên AWS**: nơi cất các Docker image đã build, để máy khác (EC2, ECS) kéo về chạy.

## Cần nhớ
- **Repository**: một "ngăn" chứa các phiên bản của một image (vd `iweb-devops`).
- **Tag**: nhãn phiên bản (`staging-2`, `develop-abc1234`). Nên dùng tag cố định theo commit để dễ quay lui; `latest` dễ gây nhầm.
- **URI đầy đủ**: `<account>.dkr.ecr.<region>.amazonaws.com/<repo>:<tag>`.
- Phải **login** bằng token tạm (12 giờ) trước khi push/pull.
- Máy EC2 kéo image bằng **IAM role** có `AmazonEC2ContainerRegistryReadOnly`, không cần lưu key.
- **Lifecycle policy**: tự xóa image cũ để khỏi tốn dung lượng.

## Lệnh hay dùng
```bash
REGION=ap-southeast-1
ACCOUNT=$(aws sts get-caller-identity --query Account --output text)
REGISTRY=$ACCOUNT.dkr.ecr.$REGION.amazonaws.com

aws ecr get-login-password --region $REGION | docker login --username AWS --password-stdin $REGISTRY

docker build -t iweb-devops .
docker tag  iweb-devops $REGISTRY/iweb-devops:staging-3
docker push $REGISTRY/iweb-devops:staging-3

aws ecr describe-images --repository-name iweb-devops --query 'imageDetails[].imageTags'
aws ecr delete-repository --repository-name iweb-devops --force   # xóa cả repo
```

## Chi phí & lưu ý
- Tính theo dung lượng lưu (khoảng 0,10 USD/GB/tháng) + dữ liệu đi ra ngoài AWS.
- Trên Windows PowerShell 5.1 đôi khi login lỗi 400: dùng Git Bash hoặc pipe qua `tr -d '\r\n'`.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
