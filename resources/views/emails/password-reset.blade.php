<!DOCTYPE html>
<html>
<head>
    <title>Đặt lại mật khẩu</title>
</head>
<body>
    <p>Chào bạn {{ $user->nickname }},</p>
    <p>Vui lòng nhấp vào liên kết bên dưới để đặt lại mật khẩu của bạn:</p>
    <p><a href="{{ $url }}">Đặt Lại Mật Khẩu</a></p>
    <p>Nếu bạn không yêu cầu điều này, vui lòng bỏ qua email này.</p>
    <p>Trân trọng,</p>
    <p>Selorson Team</p>
</body>
</html>
