# IAM (Identity and Access Management)

## Hiểu nhanh
Giống **thẻ ra vào của tòa nhà**: mỗi người (hoặc máy) có một thẻ, thẻ ghi rõ được vào tầng nào, phòng nào. IAM miễn phí.

## Cần nhớ
- **User**: một người hoặc một chương trình có tài khoản riêng (có password và/hoặc access key).
- **Group**: nhóm user, gán quyền một lần cho cả nhóm.
- **Policy**: file JSON liệt kê quyền (Allow/Deny, Action, Resource).
- **Role**: "mũ" tạm thời để đội. Máy EC2, Lambda dùng role thay vì lưu access key. **Ưu tiên role hơn access key.**
- **Nguyên tắc quyền tối thiểu**: chỉ cấp đúng thứ cần dùng.
- Mặc định mọi thứ bị **Deny**. Deny luôn thắng Allow.
- Tài khoản **root** (email đăng ký): bật MFA, không dùng hằng ngày, không tạo access key.

Ví dụ policy cho phép đọc một bucket:
```json
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Action": ["s3:GetObject", "s3:ListBucket"],
    "Resource": ["arn:aws:s3:::ten-bucket", "arn:aws:s3:::ten-bucket/*"]
  }]
}
```

## Lệnh hay dùng
```bash
aws sts get-caller-identity                  # tôi là ai
aws iam list-users
aws iam list-roles --query 'Roles[].RoleName'
aws iam list-attached-role-policies --role-name <ten-role>
aws iam create-access-key --user-name <user>     # chỉ hiện secret 1 lần!
aws iam list-access-keys --user-name <user>
```

## Chi phí & lưu ý
- Miễn phí.
- Access key bị lộ (đẩy lên Git) = mất tiền rất nhanh. Lộ thì **vô hiệu hóa ngay** (`aws iam update-access-key --status Inactive`).
- Hay gặp lỗi `AccessDenied`: đọc kỹ tên Action bị từ chối rồi thêm đúng quyền đó.

## Ghi chú của tôi
| Ngày | Ghi chú |
|---|---|
| | |
