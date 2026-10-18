# RDS (Relational Database Service)

## Hiểu nhanh
Giống **thuê người quản kho dữ liệu**: bạn chỉ lo bảng và câu truy vấn, AWS lo cài đặt, vá lỗi, sao lưu, khôi phục. Hỗ trợ MySQL, PostgreSQL, MariaDB, SQL Server, Oracle, Aurora.

## Cần nhớ
- **DB instance**: máy database do AWS quản lý, có **endpoint** (tên máy) để app kết nối.
- **Automated backup**: sao lưu hằng ngày, giữ 1 đến 35 ngày, khôi phục theo thời điểm (point-in-time).
- **Multi-AZ**: thêm bản dự phòng ở vùng khác, tự chuyển khi sự cố (tốn gấp đôi).
- **Read replica**: bản chỉ đọc để chia tải truy vấn.
- **Subnet group + Security Group**: đặt DB ở subnet private, chỉ cho SG của app vào cổng 3306/5432.
- Không SSH vào máy DB được.

Hiện staging của dự án chạy MySQL **trong Docker trên EC2**, không dùng RDS. Khi cần bền vững hơn thì chuyển sang RDS.

## Lệnh hay dùng
```bash
aws rds describe-db-instances --query 'DBInstances[].[DBInstanceIdentifier,DBInstanceStatus,Endpoint.Address]' --output table
aws rds create-db-snapshot --db-instance-identifier <id> --db-snapshot-identifier <ten-snapshot>
aws rds stop-db-instance  --db-instance-identifier <id>     # tự bật lại sau 7 ngày
aws rds start-db-instance --db-instance-identifier <id>
mysql -h <endpoint> -u <user> -p
```

## Chi phí & lưu ý
- Tính theo giờ instance + dung lượng + backup vượt hạn mức. Multi-AZ gấp đôi.
- Xóa DB thì nhớ tạo **final snapshot**, không thì mất sạch.
- Không bật "Publicly accessible" nếu không bắt buộc.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
