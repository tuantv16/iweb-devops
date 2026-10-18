# Chi phí (Billing): cách không bị "cháy túi"

## Hiểu nhanh
AWS giống **điện nước**: dùng bao nhiêu trả bấy nhiêu, quên tắt thì vẫn chạy công tơ.

## Việc nên làm ngay khi lập tài khoản
1. Bật **MFA** cho tài khoản root.
2. Vào **Billing -> Budgets**, tạo budget (vd 10 USD/tháng) có **email cảnh báo** ở 80% và 100%.
3. Bật **Cost Explorer** để xem tiền đi đâu.
4. Dùng **IAM user** hằng ngày, không dùng root.

## "Thủ phạm" hay quên (vẫn tính tiền dù không dùng)
| Thứ | Ghi chú |
|---|---|
| EC2 đang `running` | Stop khi không dùng |
| EBS volume / snapshot | Stop máy vẫn tính ổ đĩa; terminate nhưng ổ còn sót thì vẫn tính |
| Elastic IP không gắn máy | Release |
| NAT Gateway | Tính theo giờ, đắt |
| Load Balancer | Tính theo giờ |
| RDS | Stop tối đa 7 ngày rồi tự chạy lại |
| CloudWatch Logs không đặt retention | Tăng dần mãi |
| S3 / ECR image cũ | Đặt lifecycle xóa bớt |

## Dọn dẹp khi học xong (thứ tự gợi ý)
Terminate EC2 -> release Elastic IP -> xóa Security Group -> xóa ECR repo -> xóa IAM role/instance profile -> xóa key pair.
## Lệnh hay dùng
```bash
# tổng tiền tháng này theo dịch vụ
aws ce get-cost-and-usage --time-period Start=2026-10-01,End=2026-10-31 \
  --granularity MONTHLY --metrics UnblendedCost --group-by Type=DIMENSION,Key=SERVICE

# tìm ổ đĩa mồ côi và IP chưa gắn
aws ec2 describe-volumes --filters Name=status,Values=available --query 'Volumes[].[VolumeId,Size]'
aws ec2 describe-addresses --query 'Addresses[?AssociationId==null].PublicIp'
```

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
