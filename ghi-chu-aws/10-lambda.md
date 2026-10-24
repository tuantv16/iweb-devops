# Lambda

## Hiểu nhanh
Giống **gọi người giúp việc theo giờ**: có việc thì họ đến làm rồi đi, bạn chỉ trả tiền cho thời gian làm thật, không phải thuê cả ngày.

## Cần nhớ
- Bạn đưa **một hàm** (Python, Node.js, Java, Go, .NET...). AWS lo máy chủ.
- **Trigger** (nguồn kích hoạt): API Gateway (HTTP), S3 (có file mới), EventBridge (hẹn giờ kiểu cron), SQS, SNS...
- **Giới hạn**: chạy tối đa **15 phút** mỗi lần, bộ nhớ 128 MB - 10 GB, ổ tạm `/tmp` 512 MB mặc định.
- **Cold start**: lần gọi đầu sau thời gian rảnh chậm hơn một chút.
- Mỗi hàm có **execution role** (IAM) quy định nó được gọi dịch vụ nào. Log tự động vào CloudWatch.
- **Biến môi trường** để cấu hình, bí mật nên để trong Secrets Manager/SSM.

Ví dụ hàm Python:
```python
def handler(event, context):
    return {"statusCode": 200, "body": "xin chao"}
```

## Lệnh hay dùng
```bash
aws lambda list-functions --query 'Functions[].[FunctionName,Runtime]' --output table
aws lambda invoke --function-name <ten> --payload '{"a":1}' --cli-binary-format raw-in-base64-out out.json
aws logs tail /aws/lambda/<ten> --follow
aws lambda update-function-code --function-name <ten> --zip-file fileb://function.zip
```

## Chi phí & lưu ý
- Free tier: 1 triệu request và 400.000 GB-giây mỗi tháng. Sau đó tính theo số lần gọi + thời gian chạy.
- Hàm gọi lại chính nó (vòng lặp vô hạn) có thể ra hóa đơn lớn: đặt **reserved concurrency** hoặc alarm.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
