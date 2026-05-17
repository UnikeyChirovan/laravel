<!DOCTYPE html>
<html>
<head>
    <title>{{ $notification->title }}</title>
</head>
<body>
    <h1>{{ $notification->title }}</h1>
    <p>{{ $notification->content }}</p>

    <hr>
    <p>Nếu bạn không muốn nhận thông báo nữa, bạn có thể <a href="{{ url('/api/newsletter/unsubscribe?email=' . urlencode($subscriber->email)) }}">hủy đăng ký tại đây</a>.</p>
</body>
</html>
