# CloudWatch

## Hiểu nhanh
Giống **camera giám sát + sổ nhật ký + chuông báo động** cho hệ thống. Xem được log, biểu đồ CPU/RAM, và được báo khi có chuyện.

Chi tiết cách đẩy log Laravel lên CloudWatch: xem [../huong-dan-cloudwatch.md](../huong-dan-cloudwatch.md).

## Cần nhớ
- **Logs**: log group (nhóm, vd một ứng dụng) -> log stream (từng nguồn, vd một container). Có thể đặt **retention** (giữ bao lâu).
- **Metrics**: số đo theo thời gian (CPU, network, số request...). EC2 mặc định có CPU, network, disk I/O nhưng **không có RAM và dung lượng đĩa** (cần cài CloudWatch Agent).
- **Alarm**: so sánh metric với ngưỡng, vượt thì làm gì đó (gửi email qua **SNS**, dừng máy...).
- **Logs Insights**: ngôn ngữ truy vấn để tìm trong log.
- **Dashboard**: bảng điều khiển tự dựng.

## Lệnh hay dùng
```bash
aws logs describe-log-groups --query 'logGroups[].logGroupName'
aws logs tail /iweb-devops/staging --follow --since 10m           # xem log trực tiếp
aws logs put-retention-policy --log-group-name /iweb-devops/staging --retention-in-days 14

aws cloudwatch describe-alarms --query 'MetricAlarms[].[AlarmName,StateValue]' --output table
```

Mẫu truy vấn Logs Insights (tìm lỗi mới nhất):
```
fields @timestamp, @message
| filter @message like /ERROR/
| sort @timestamp desc
| limit 50
```

## Chi phí & lưu ý
- Log tính theo dung lượng nhận vào và dung lượng lưu. **Luôn đặt retention**, mặc định là giữ mãi.
- Đừng ghi dữ liệu nhạy cảm (mật khẩu, nội dung người dùng) vào log.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
