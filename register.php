<?php
require_once 'db_connect.php'; // Session started here

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - SmartCity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #00A78E; /* Màu chủ đạo */
            --primary-hover: #008c75;
            --bg-gradient: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%);
        }

        body.register-page {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh; /* Dùng min-height để tránh bị cắt khi form dài */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 0; /* Thêm padding để form không dính sát mép trên/dưới */
        }

        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 167, 142, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 500px; /* Rộng hơn login một chút vì nhiều trường hơn */
            background: white;
        }

        .login-card-body {
            padding: 40px;
        }

        .brand-logo {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-title {
            text-align: center;
            color: #666;
            font-weight: 500;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        /* Style Input giống Login */
        .input-group-custom {
            position: relative;
            margin-bottom: 15px; /* Giảm khoảng cách chút cho đỡ dài */
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            z-index: 10;
            transition: color 0.3s;
        }

        .form-control {
            height: 50px;
            border-radius: 10px;
            padding-left: 45px;
            border: 2px solid #eee;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 167, 142, 0.1);
        }

        .form-control:focus + .input-icon {
            color: var(--primary-color);
        }

        /* Nút Đăng ký */
        .btn-register {
            background-color: var(--primary-color);
            border: none;
            height: 50px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 167, 142, 0.3);
            margin-top: 10px;
        }

        .btn-register:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 167, 142, 0.4);
        }

        .login-link {
            margin-top: 25px;
            font-size: 0.9rem;
            color: #666;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Checkbox màu xanh ngọc */
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-label-custom {
            font-size: 0.9rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
    </style>
</head>
<body class="register-page"> 
    
    <div class="login-card">
        <div class="card-body login-card-body">
            
            <div class="brand-logo">
                <i class="fas fa-city"></i> SmartCity
            </div>
            <p class="login-title">Tạo tài khoản công dân mới</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="register_action.php" method="POST" id="registerForm">
                
                <div class="mb-3">
                    <label for="fullname" class="form-label-custom">Họ và Tên</label>
                    <div class="input-group-custom">
                        <input type="text" class="form-control" id="fullname" name="fullname" required placeholder="Nguyễn Văn A">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label-custom">Email</label>
                    <div class="input-group-custom">
                        <input type="email" class="form-control" id="email" name="email" required placeholder="name@example.com">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="phone_number" class="form-label-custom">Số điện thoại </label>
                    <div class="input-group-custom">
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="09xxxxxxxx">
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label-custom">Mật khẩu</label>
                    <div class="input-group-custom position-relative">
                        <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••" style="padding-right: 40px;">
                        <i class="fas fa-lock input-icon"></i>
                        <i class="fas fa-eye position-absolute end-0 top-50 translate-middle-y me-3 cursor-pointer toggle-password" 
                        style="cursor: pointer; color: #aaa; z-index: 20;" 
                        onclick="togglePassword('password', this)"></i>
                    </div>
                </div>

                <div class="mb-3">
                <label for="confirm_password" class="form-label-custom">Xác nhận mật khẩu</label>
                <div class="input-group-custom position-relative">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="••••••••" style="padding-right: 40px;">
                    <i class="fas fa-shield-alt input-icon"></i>
                    <i class="fas fa-eye position-absolute end-0 top-50 translate-middle-y me-3 cursor-pointer toggle-password" 
                    style="cursor: pointer; color: #aaa; z-index: 20;" 
                    onclick="togglePassword('confirm_password', this)"></i>
                </div>
                    <div id="passwordHelp" class="text-danger small d-none mt-1"><i class="fas fa-times-circle"></i> Mật khẩu không khớp.</div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="agreed" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label text-secondary small" for="agree_terms">
                        Tôi đồng ý với <a href="#" target="_blank" style="color: var(--primary-color);">Điều khoản Dịch vụ</a>
                    </label>
                    <div id="termsHelp" class="text-danger small d-none mt-1">Bạn phải đồng ý với điều khoản.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i> Đăng ký tài khoản
                    </button>
                </div>

            </form>

            <div class="text-center login-link">
                Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- Logic JS Kiểm tra mật khẩu & Điều khoản (Giữ nguyên logic của bạn) ---
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordHelp = document.getElementById('passwordHelp');
        const termsCheckbox = document.getElementById('agree_terms');
        const termsHelp = document.getElementById('termsHelp');
        const registerForm = document.getElementById('registerForm');

        function validatePasswords() {
            if (passwordInput.value !== confirmPasswordInput.value && confirmPasswordInput.value !== '') {
                confirmPasswordInput.classList.add('is-invalid'); // Bootstrap class
                // Cần chỉnh CSS cho class is-invalid nếu muốn nó không đè lên viền custom
                confirmPasswordInput.style.borderColor = '#dc3545'; 
                passwordHelp.classList.remove('d-none');
                return false;
            } else {
                confirmPasswordInput.classList.remove('is-invalid');
                confirmPasswordInput.style.borderColor = ''; // Reset về mặc định
                passwordHelp.classList.add('d-none');
                return true;
            }
        }

        confirmPasswordInput.addEventListener('input', validatePasswords);
        passwordInput.addEventListener('input', validatePasswords);

        registerForm.addEventListener('submit', function(event) {
            let passwordsMatch = validatePasswords();
            let termsAgreed = termsCheckbox.checked;

            if (!termsAgreed) {
                termsCheckbox.classList.add('is-invalid');
                termsHelp.classList.remove('d-none');
            } else {
                 termsCheckbox.classList.remove('is-invalid');
                 termsHelp.classList.add('d-none');
            }

            if (!passwordsMatch || !termsAgreed) {
                event.preventDefault();
            } 
            
            else {
                const btn = this.querySelector('button[type="submit"]');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang đăng ký...';
                btn.style.opacity = '0.7';
                btn.style.pointerEvents = 'none'; // Khóa nút lại
            }
        });

         termsCheckbox.addEventListener('change', function() {
              if (termsCheckbox.checked) {
                  termsCheckbox.classList.remove('is-invalid');
                  termsHelp.classList.add('d-none');
              }
        });

         function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
            if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
            } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
            }
    }
    </script>
    <?php include 'includes/sweetalert.php'; ?>
</body>
</html>