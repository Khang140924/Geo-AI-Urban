<?php
session_start();
require_once 'db_connect.php';

// --- CẤU HÌNH PHPMAILER ---
// Đảm bảo bạn đã copy thư mục PHPMailer vào đúng chỗ
// Nếu báo lỗi "No such file", hãy kiểm tra lại đường dẫn này
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Lấy dữ liệu
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree_terms = isset($_POST['agree_terms']);

    // 2. Validation
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: register.php?error=Vui lòng điền đủ thông tin');
        exit;
    }
    if ($password !== $confirm_password) {
        header('Location: register.php?error=Mật khẩu không khớp');
        exit;
    }
    if (!$agree_terms) {
        header('Location: register.php?error=Bạn chưa đồng ý điều khoản');
        exit;
    }

    // Số điện thoại: Rỗng thì lưu NULL
    $phone_to_save = !empty($phone_number) ? $phone_number : null;

    try {
        // 3. Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            header('Location: register.php?error=Email này đã được sử dụng');
            exit;
        }

        // 4. Tạo mã OTP và Hash mật khẩu
        $otp = rand(100000, 999999); // Mã 6 số ngẫu nhiên
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // 5. Lưu vào CSDL (Trạng thái chưa kích hoạt)
        // Lưu ý: Cột verification_code phải được tạo trong SQL rồi nhé
        $sql = "INSERT INTO users (fullname, email, password_hash, role, phone_number, avatar_url, is_active, created_at, verification_code) 
                VALUES (:name, :email, :pass, 'user', :phone, '', 0, GETDATE(), :otp)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $fullname,
            ':email' => $email,
            ':pass' => $password_hash,
            ':phone' => $phone_to_save,
            ':otp' => $otp
        ]);

        // 6. Gửi Email OTP bằng PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Cấu hình Server Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            
            // --- [QUAN TRỌNG] THAY THÔNG TIN CỦA BẠN VÀO ĐÂY ---
            $mail->Username   = 'khangcachep1234@gmail.com'; // Email của bạn
            $mail->Password   = 'dvol krnt eahy ezfs'; // Mật khẩu ứng dụng (App Password)
            // -----------------------------------------------------

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8'; // Để gửi tiếng Việt không lỗi font

            // Người gửi và người nhận
            $mail->setFrom('email_cua_ban@gmail.com', 'SmartCity Support'); // Thay email của bạn vào đây
            $mail->addAddress($email, $fullname); // Gửi đến email người đăng ký

            // Nội dung email
            $mail->isHTML(true);
            $mail->Subject = 'Mã xác thực tài khoản SmartCity';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                    <h3>Xin chào $fullname,</h3>
                    <p>Cảm ơn bạn đã đăng ký tham gia <b>SmartCity</b>.</p>
                    <p>Mã xác thực của bạn là:</p>
                    <h2 style='color: #00A78E; letter-spacing: 5px;'>$otp</h2>
                    <p>Vui lòng nhập mã này để kích hoạt tài khoản.</p>
                    <p>Trân trọng,<br>Đội ngũ SmartCity</p>
                </div>
            ";

            $mail->send();

            // 7. Gửi xong -> Chuyển sang trang nhập mã
            $_SESSION['verify_email'] = $email; // Lưu email để trang verify biết của ai
            header('Location: verify.php');
            exit;

        } catch (Exception $e) {
            // Nếu gửi mail thất bại -> Xóa user vừa tạo để tránh rác data
            $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
            header("Location: register.php?error=Lỗi gửi mail: " . $mail->ErrorInfo);
            exit;
        }

    } catch (Exception $e) {
        header('Location: register.php?error=Lỗi hệ thống: ' . $e->getMessage());
        exit;
    }
} else {
    header('Location: register.php');
    exit;
}
?>