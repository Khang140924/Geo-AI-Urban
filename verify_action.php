<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = $_POST['otp'];
    $email = $_SESSION['verify_email'];

    if (empty($otp) || empty($email)) {
        header("Location: verify.php?error=Vui lòng nhập mã");
        exit;
    }

    try {
        // 1. Kiểm tra mã trong CSDL
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ?");
        $stmt->execute([$email, $otp]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Mã đúng -> Kích hoạt tài khoản (is_active = 1) và xóa mã OTP
            $update = $pdo->prepare("UPDATE users SET is_active = 1, verification_code = NULL WHERE id = ?");
            $update->execute([$user['id']]);

            // 3. Xóa session tạm và chuyển sang đăng nhập
            unset($_SESSION['verify_email']);
            header("Location: login.php?success=Tài khoản đã kích hoạt! Vui lòng đăng nhập.");
            exit;
        } else {
            header("Location: verify.php?error=Mã xác thực không đúng");
            exit;
        }

    } catch (Exception $e) {
        header("Location: verify.php?error=Lỗi hệ thống");
    }
}
?>