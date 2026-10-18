# ELB (Elastic Load Balancing)

## Hiểu nhanh
Giống **nhân viên đứng quầy tiếp đón**: khách (request) đến, chia đều cho các quầy (server) đang rảnh, quầy nào nghỉ thì không dẫn khách tới.

## Cần nhớ
- **ALB** (Application LB): tầng HTTP/HTTPS, định tuyến theo đường dẫn/host (`/api` -> nhóm A). Dùng cho web, phổ biến nhất.
- **NLB** (Network LB): tầng TCP/UDP, tốc độ cao, IP cố định.
- **Target group**: nhóm server nhận khách. **Health check** liên tục kiểm tra, server hỏng tự bị loại.
- **Listener**: cổng lắng nghe (80, 443) và quy tắc chuyển tiếp.
- **HTTPS**: gắn chứng chỉ miễn phí từ **ACM**, load balancer lo mã hóa, server phía sau chạy HTTP.
- Đặt ở subnet public, server thật ở subnet private, SG của server chỉ nhận từ SG của LB.

Staging hiện chỉ có **một** EC2 nên chưa cần ELB. Cần khi chạy nhiều máy hoặc muốn HTTPS chuẩn.

## Lệnh hay dùng
```bash
aws elbv2 describe-load-balancers --query 'LoadBalancers[].[LoadBalancerName,DNSName,State.Code]' --output table
aws elbv2 describe-target-groups
aws elbv2 describe-target-health --target-group-arn <arn>
```

## Chi phí & lưu ý
- Tính theo giờ (khoảng 16-20 USD/tháng cho ALB nhỏ) + lượng dữ liệu xử lý. Dễ quên xóa khi học.
- Target `unhealthy`: kiểm tra đường dẫn health check có trả 200 không, và SG có cho LB vào không.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
