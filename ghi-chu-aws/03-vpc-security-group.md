# VPC & Security Group

## Hiểu nhanh
- **VPC** giống **khu đất riêng của bạn trong thành phố AWS**, có hàng rào và đường đi riêng.
- **Subnet** là các khu nhỏ trong khu đất đó.
- **Security Group (SG)** giống **bảo vệ đứng trước cửa từng căn nhà**: chỉ cho vào đúng cổng, đúng người.

## Cần nhớ
- **VPC**: mạng ảo riêng, có dải IP (CIDR) vd `10.0.0.0/16`. Mỗi account có sẵn một *default VPC*.
- **Subnet public**: có đường ra internet qua **Internet Gateway**. **Subnet private**: không có, ra ngoài qua **NAT Gateway** (tốn tiền).
- **Route table**: bảng chỉ đường của subnet.
- **Security Group**: tường lửa gắn vào máy/dịch vụ, **stateful** (cho vào thì chiều về tự được phép).
  - Chỉ có luật **Allow**, mặc định chặn hết inbound.
  - Luật gồm: giao thức, cổng, nguồn (`1.2.3.4/32` = đúng 1 IP, `0.0.0.0/0` = cả internet).
- Cổng hay gặp: 22 SSH, 80 HTTP, 443 HTTPS, 3306 MySQL, 5432 Postgres.
- Database **không nên** mở ra internet, chỉ cho SG của app truy cập.

## Lệnh hay dùng
```bash
aws ec2 describe-vpcs --query 'Vpcs[].[VpcId,CidrBlock,IsDefault]' --output table
aws ec2 describe-subnets --query 'Subnets[].[SubnetId,VpcId,CidrBlock,AvailabilityZone]' --output table
aws ec2 describe-security-groups --group-ids <sg-xxx>

# mở SSH cho IP của tôi
aws ec2 authorize-security-group-ingress --group-id <sg-xxx> --protocol tcp --port 22 --cidr <ip-cua-toi>/32
# đóng lại
aws ec2 revoke-security-group-ingress    --group-id <sg-xxx> --protocol tcp --port 22 --cidr <ip-cu>/32
```

## Chi phí & lưu ý
- VPC, subnet, SG miễn phí. **NAT Gateway** tính tiền theo giờ + dữ liệu, rất dễ quên.
- IP nhà đổi (nhà mạng cấp động) thì SSH bị chặn: cập nhật lại luật cổng 22.
- Không thể SSH được? Kiểm tra theo thứ tự: máy đang `running` -> SG có mở 22 cho IP mình -> đúng key, đúng user.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
