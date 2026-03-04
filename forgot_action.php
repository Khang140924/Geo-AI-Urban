<?php
session_start();
require_once 'db_connect.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    try {
        // 1. Kiểm tra email có tồn tại không
        $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Tạo Token (Chuỗi ngẫu nhiên 64 ký tự)
            $token = bin2hex(random_bytes(32));
            // Hết hạn sau 1 tiếng (Cộng thêm 3600 giây)
            // Lưu ý: Format ngày tháng chuẩn SQL Server (Y-m-d H:i:s)
            $expiry = date('Y-m-d H:i:s', time() + 3600); 

            // 3. Lưu Token vào DB
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            $update->execute([$token, $expiry, $email]);

            // 4. Gửi Email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            
            // --- THAY THÔNG TIN CỦA BẠN ---
            $mail->Username   = 'khangcachep1234@gmail.com';
            $mail->Password   = 'dvol krnt eahy ezfs';
            // -------------------------------

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('email_cua_ban@gmail.com', 'SmartCity Support');
            $mail->addAddress($email);

            // Tạo Link reset
            // Lưu ý: Sửa 'localhost/DOAN_CN' cho đúng đường dẫn máy bạn
            $resetLink = "http://localhost/DOAN_CN/reset_password.php?email=$email&token=$token";

            $mail->isHTML(true);
            $mail->Subject = 'Khôi phục mật khẩu SmartCity';
            $mail->Body    = "
                <h3>Xin chào {$user['fullname']},</h3>
                <p>Bạn vừa yêu cầu đặt lại mật khẩu.</p>
                <p>Vui lòng bấm vào link dưới đây để tạo mật khẩu mới (Link hết hạn sau 1 giờ):</p>
                <p><a href='$resetLink' style='background: #00A78E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Đặt lại mật khẩu</a></p>
                <p>Hoặc copy link này: $resetLink</p>
            ";

            $mail->send();
            header("Location: forgot_password.php?success=Đã gửi link khôi phục vào email!");
        } else {
            // Email không tồn tại: Vẫn báo thành công để Hacker không dò được email nào có thật
            // (Hoặc báo lỗi nếu bạn thích: "Email không tồn tại")
            header("Location: forgot_password.php?error=Email không tồn tại trong hệ thống.");
        }

    } catch (Exception $e) {
        header("Location: forgot_password.php?error=Lỗi: " . $e->getMessage());
    }
}
?>