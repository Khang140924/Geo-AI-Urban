<?php
require_once 'db_connect.php'; // Đã có session_start()

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header('Location: login.php?error=Vui lòng điền đầy đủ thông tin.');
        exit;
    }

    try {
        // 1. Tìm người dùng
        // === SỬA Ở ĐÂY: Thêm 'is_active' vào câu SELECT ===
        $stmt = $pdo->prepare("SELECT id, fullname, email, password_hash, role, avatar_url, is_active FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        // 2. Kiểm tra mật khẩu
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 3. === SỬA Ở ĐÂY: Kiểm tra tài khoản có bị khóa không ===
            if ($user['is_active'] != 1) {
                // Tài khoản đã bị khóa (is_active = 0)
                header('Location: login.php?error=Tài khoản của bạn đã bị khóa.');
                exit;
            }
            
            // 4. Đăng nhập thành công: Lưu vào SESSION
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_fullname'] = $user['fullname'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar_url'] = $user['avatar_url']; 
            
            // 5. Chuyển hướng
            if ($user['role'] == 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;

        } else {
            // Sai thông tin
            header('Location: login.php?error=Email hoặc mật khẩu không chính xác.');
            exit;
        }

    } catch (Exception $e) {
        header('Location: login.php?error=Lỗi hệ thống: ' . $e->getMessage());
        exit;
    }
}
?>