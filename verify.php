<?php
session_start();
if (!isset($_SESSION['verify_email'])) {
    header('Location: register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác thực tài khoản - SmartCity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #00A78E; --bg-gradient: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%); }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-gradient); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 167, 142, 0.15); width: 100%; max-width: 400px; }
        .btn-verify { background-color: var(--primary-color); color: white; border-radius: 10px; height: 45px; font-weight: 600; width: 100%; border: none; }
        .btn-verify:hover { background-color: #008c75; }
        .form-control { text-align: center; letter-spacing: 5px; font-size: 1.5rem; }
    </style>
</head>
<body>
    <div class="card p-4">
        <div class="text-center mb-4">
            <h3 style="color: var(--primary-color); font-weight: 700;">Xác thực Email</h3>
            <p class="text-muted small">Chúng tôi đã gửi mã 6 số đến:<br><strong><?php echo $_SESSION['verify_email']; ?></strong></p>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center p-2 small"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="verify_action.php" method="POST">
            <div class="mb-4">
                <input type="text" name="otp" class="form-control" maxlength="6" required placeholder="000000" autofocus>
            </div>
            <button type="submit" class="btn btn-verify">Xác nhận</button>
        </form>
        <div class="text-center mt-3">
            <a href="register.php" class="text-decoration-none small text-secondary">Quay lại đăng ký</a>
        </div>
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success text-center p-2 small">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-3 d-flex justify-content-between px-2">
            <a href="register.php" class="text-decoration-none small text-secondary">
                <i class="fas fa-arrow-left"></i> Đăng ký lại
            </a>
            <a href="#" id="btn-resend" class="text-decoration-none small" style="color: var(--primary-color); font-weight: 600;">
                <i class="fas fa-redo"></i> <span id="resend-text">Gửi lại mã</span>
            </a>
        </div>
    </div>
</body>
<script>
    const btnResend = document.getElementById('btn-resend');
    const resendText = document.getElementById('resend-text');
    let countdownInterval;

    // 1. Hàm cập nhật giao diện (Bật/Tắt nút)
    function updateUI(remaining) {
        if (remaining > 0) {
            // Đang đếm ngược: Khóa nút
            btnResend.style.pointerEvents = 'none';
            btnResend.style.opacity = '0.5';
            btnResend.style.color = '#999'; // Đổi màu xám cho thấy đang khóa
            resendText.innerText = `Gửi lại sau (${remaining}s)`;
        } else {
            // Hết giờ: Mở nút & Dọn dẹp
            clearInterval(countdownInterval);
            localStorage.removeItem('otp_end_time'); // Xóa mốc thời gian trong bộ nhớ
            
            btnResend.style.pointerEvents = 'auto';
            btnResend.style.opacity = '1';
            btnResend.style.color = ''; // Trả lại màu gốc (xanh)
            resendText.innerText = "Gửi lại mã";
        }
    }

    // 2. Hàm chạy bộ đếm
    function startCountdown() {
        // Xóa interval cũ nếu có để tránh chạy chồng chéo
        if (countdownInterval) clearInterval(countdownInterval);

        countdownInterval = setInterval(() => {
            const endTime = parseInt(localStorage.getItem('otp_end_time'));
            
            // Nếu không có mốc thời gian thì dừng ngay
            if (!endTime) {
                updateUI(0);
                return;
            }

            const now = Math.floor(Date.now() / 1000);
            const remaining = endTime - now;

            if (remaining >= 0) {
                updateUI(remaining);
            } else {
                updateUI(0); // Về 0 thì dừng hẳn
            }
        }, 1000);
    }

    // 3. Sự kiện khi bấm nút
    btnResend.addEventListener('click', function(e) {
        // Nếu đang khóa thì không cho bấm
        if (btnResend.style.pointerEvents === 'none') {
            e.preventDefault();
            return;
        }
        
        // Đặt mốc thời gian kết thúc là: Bây giờ + 60 giây
        const endTime = Math.floor(Date.now() / 1000) + 60;
        localStorage.setItem('otp_end_time', endTime);

        // Chuyển hướng
        window.location.href = 'resend_otp.php';
    });

    // 4. Tự động chạy khi tải trang (Nếu có lưu trong bộ nhớ)
    const savedEndTime = localStorage.getItem('otp_end_time');
    if (savedEndTime) {
        const now = Math.floor(Date.now() / 1000);
        const remaining = parseInt(savedEndTime) - now;

        if (remaining > 0) {
            updateUI(remaining); // Cập nhật số giây ngay lập tức
            startCountdown();    // Bắt đầu đếm
        } else {
            localStorage.removeItem('otp_end_time'); // Đã hết hạn từ lâu -> Xóa luôn
            updateUI(0);
        }
    }
</script>
</html>