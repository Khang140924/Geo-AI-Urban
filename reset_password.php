<?php
require_once 'db_connect.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$error = '';
$success = false;

// Kiểm tra Token có hợp lệ không
if ($email && $token) {
    // Lấy token và thời gian hết hạn
    $stmt = $pdo->prepare("SELECT reset_token, reset_expiry FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || $user['reset_token'] !== $token) {
        $error = "Link khôi phục không hợp lệ hoặc đã được sử dụng.";
    } else {
        // Kiểm tra hết hạn
        if (strtotime($user['reset_expiry']) < time()) {
            $error = "Link đã hết hạn. Vui lòng yêu cầu lại.";
        }
    }
} else {
    $error = "Thiếu thông tin xác thực.";
}

// Xử lý khi bấm nút Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($pass !== $confirm) {
        $error = "Mật khẩu không khớp.";
    } else {
        // Cập nhật mật khẩu mới
        $new_hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Xóa token để không dùng lại được nữa
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expiry = NULL WHERE email = ?");
        $upd->execute([$new_hash, $email]);
        
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-color: #00A78E; }
        body { background: #f0f9f8; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; }
        .card { width: 100%; max-width: 450px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; padding: 30px; }
        .btn-custom { background: var(--primary-color); color: white; width: 100%; height: 50px; border-radius: 10px; border: none; font-weight: 600; }
        .form-control { height: 50px; border-radius: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($success): ?>
            <div class="text-center">
                <h2 class="text-success"><i class="fas fa-check-circle"></i></h2>
                <h3>Thành công!</h3>
                <p>Mật khẩu của bạn đã được thay đổi.</p>
                <a href="login.php" class="btn btn-custom mt-3">Đăng nhập ngay</a>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="text-center">
                <h2 class="text-danger"><i class="fas fa-times-circle"></i></h2>
                <h3 class="text-danger">Lỗi!</h3>
                <p><?php echo $error; ?></p>
                <a href="forgot_password.php" class="btn btn-secondary mt-3">Thử lại</a>
            </div>
        <?php else: ?>
            <h3 class="text-center mb-4" style="color: #00A78E; font-weight: 700;">Mật khẩu mới</h3>
            <form method="POST">
                <input type="password" name="password" class="form-control" placeholder="Mật khẩu mới" required>
                <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>
                <button type="submit" class="btn btn-custom">Đổi mật khẩu</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>