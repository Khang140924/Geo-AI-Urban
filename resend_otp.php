<?php
session_start();
require_once 'db_connect.php';

// Nhúng PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['verify_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['verify_email'];

try {
    // 1. Tạo mã OTP mới
    $new_otp = rand(100000, 999999);

    // 2. Cập nhật vào CSDL
    $stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
    $stmt->execute([$new_otp, $email]);

    // 3. Gửi lại Email
    $mail = new PHPMailer(true);
    
    // --- CẤU HÌNH GMAIL (COPY Y CHANG FILE ĐĂNG KÝ) ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'khangcachep1234@gmail.com'; // <--- THAY EMAIL CỦA BẠN
    $mail->Password   = 'dvol krnt eahy ezfs'; // <--- THAY MẬT KHẨU CỦA BẠN
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('email_cua_ban@gmail.com', 'SmartCity Support');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Gửi lại mã xác thực - SmartCity';
    $mail->Body    = "
        <p>Bạn vừa yêu cầu gửi lại mã xác thực.</p>
        <p>Mã mới của bạn là: <b style='font-size: 20px; color: #00A78E;'>$new_otp</b></p>
    ";

    $mail->send();

    // Gửi xong quay lại trang nhập mã và báo thành công
    header("Location: verify.php?success=Đã gửi lại mã mới vào email!");
    exit;

} catch (Exception $e) {
    header("Location: verify.php?error=Không thể gửi lại mã. Lỗi: {$mail->ErrorInfo}");
    exit;
}
?>