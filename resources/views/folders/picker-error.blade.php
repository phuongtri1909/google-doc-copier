<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi Chọn Thư Mục</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; text-align: center; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 8px 16px; background: #0d6efd; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Không thể truy cập Google Drive</h1>
        <div class="error">
            <p>{{ $error }}</p>
        </div>
        <p>Vui lòng đăng nhập và thử lại.</p>
        <a href="{{ route('auth.google') }}" target="_blank" class="btn">Đăng nhập Google</a>
        <p><a href="javascript:window.close()">Đóng cửa sổ này</a></p>
    </div>
</body>
</html>