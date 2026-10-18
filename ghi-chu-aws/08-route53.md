# Route 53

## Hiểu nhanh
Giống **danh bạ điện thoại của internet**: bạn gõ tên (`tuan.com`), danh bạ trả về số (IP của server).

## Cần nhớ
- **Hosted zone**: nơi chứa các bản ghi DNS của một tên miền.
- **Các loại bản ghi**:
  - `A`: tên -> IPv4
  - `AAAA`: tên -> IPv6
  - `CNAME`: tên -> tên khác (không dùng được cho domain gốc `tuan.com`)
  - `Alias`: riêng của AWS, trỏ tên (kể cả domain gốc) tới ELB, CloudFront, S3... miễn phí truy vấn
  - `MX`: mail, `TXT`: xác minh/SPF
- **TTL**: bản ghi được cache bao lâu. Đổi IP thì đợi hết TTL cũ.
- Mua domain ở Route 53 hoặc nơi khác đều được; chỉ cần trỏ **name server (NS)** về hosted zone.
- Có thể kèm **health check** và định tuyến theo vùng/trọng số.

## Lệnh hay dùng
```bash
aws route53 list-hosted-zones
aws route53 list-resource-record-sets --hosted-zone-id <Zxxxx>

# tạo/sửa bản ghi A (file change.json)
aws route53 change-resource-record-sets --hosted-zone-id <Zxxxx> --change-batch file://change.json
```
`change.json`:
```json
{ "Changes": [{ "Action": "UPSERT", "ResourceRecordSet": {
    "Name": "www.tuan.com", "Type": "A", "TTL": 300,
    "ResourceRecords": [{ "Value": "203.0.113.10" }] } }] }
```

## Chi phí & lưu ý
- Hosted zone 0,50 USD/tháng + phí truy vấn nhỏ; domain tính theo năm (khoảng 12 USD với `.com`).
- Kiểm tra DNS: `nslookup ten-mien` hoặc `dig ten-mien`.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
