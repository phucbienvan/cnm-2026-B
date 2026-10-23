<!DOCTYPE html>
<html lang="vi">
<body>
    <p>Xin chào {{ $user->name }},</p>
    <p>Mã xác nhận tài khoản của bạn là: <strong>{{ $user->code }}</strong></p>
    <p>Mã có hiệu lực đến {{ $user->expired_code->format('H:i d/m/Y') }}.</p>
</body>
</html>
