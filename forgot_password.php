<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu - SmartCity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --primary-color: #00A78E; --bg-gradient: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%); }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-gradient); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 167, 142, 0.15); width: 100%; max-width: 450px; padding: 30px; }
        .btn-custom { background-color: var(--primary-color); color: white; border-radius: 10px; height: 50px; font-weight: 600; width: 100%; border: none; transition: 0.3s; }
        .btn-custom:hover { background-color: #008c75; transform: translateY(-2px); }
        .form-control { height: 50px; border-radius: 10px; padding-left: 45px; }
        .input-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
        .input-group-custom { position: relative; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="text-center mb-4">
            <h3 style="color: var(--primary-color); font-weight: 700;">Quên mật khẩu?</h3>
            <p class="text-muted small">Đừng lo, hãy nhập email để lấy lại mật khẩu.</p>
        </div>

        <form action="forgot_action.php" method="POST">
            <div class="input-group-custom">
                <input type="email" class="form-control" name="email" required placeholder="Nhập email của bạn">
                <i class="fas fa-envelope input-icon"></i>
            </div>
            <button type="submit" class="btn btn-custom">Gửi link khôi phục</button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none small text-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại đăng nhập
            </a>
        </div>
    </div>
    
    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const btn = this.querySelector('button');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
        });
    </script>
    <?php include 'includes/sweetalert.php'; ?>
</body>
</html>