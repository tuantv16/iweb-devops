# Sổ tay AWS cơ bản

Mỗi file = một dịch vụ. Cấu trúc giống nhau để dễ tra:

1. **Hiểu nhanh**: ví dụ đời thường
2. **Cần nhớ**: khái niệm cốt lõi
3. **Lệnh hay dùng**: AWS CLI
4. **Chi phí & lưu ý**: chỗ dễ bị tính tiền hoặc dễ sai
5. **Ghi chú của tôi**: chỗ để tự viết hằng ngày

## Mục lục

| File | Dịch vụ | Một câu |
|---|---|---|
| [01-iam.md](01-iam.md) | IAM | Ai được làm gì |
| [02-ec2.md](02-ec2.md) | EC2 | Máy chủ ảo |
| [03-vpc-security-group.md](03-vpc-security-group.md) | VPC, Security Group | Mạng riêng và tường lửa |
| [04-s3.md](04-s3.md) | S3 | Kho chứa file |
| [05-rds.md](05-rds.md) | RDS | Database có người trông hộ |
| [06-ecr.md](06-ecr.md) | ECR | Kho chứa Docker image |
| [07-cloudwatch.md](07-cloudwatch.md) | CloudWatch | Log, biểu đồ, cảnh báo |
| [08-route53.md](08-route53.md) | Route 53 | Tên miền và DNS |
| [09-elb.md](09-elb.md) | ELB | Bộ chia tải |
| [10-lambda.md](10-lambda.md) | Lambda | Chạy code không cần server |
| [11-chi-phi.md](11-chi-phi.md) | Billing | Cách không bị "cháy túi" |
| [nhat-ky-hang-ngay.md](nhat-ky-hang-ngay.md) | Nhật ký | Mẫu ghi chú mỗi ngày |

## Thiết lập chung (làm một lần)

```bash
aws configure                  # nhập Access Key, Secret, region (vd ap-southeast-1), output json
aws sts get-caller-identity    # đang dùng tài khoản/user nào?
export AWS_PAGER=""            # tắt trình phân trang của CLI cho gọn
```

Dự án này đang dùng region `ap-southeast-1` (Singapore). ID tài khoản và tài nguyên staging không ghi ở đây (repo có thể công khai), hãy tra trong AWS Console hoặc ghi vào file riêng không commit.
