<?php
require_once 'db_connect.php'; 

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// --- PHẦN XỬ LÝ LOGIC GOOGLE (Sẽ thêm sau) ---
$google_login_url = "login_google_action.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - SmartCity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #00A78E; 
            --primary-hover: #008c75;
            --bg-gradient: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%);
        }

        body.login-page {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 167, 142, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
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
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
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

        .btn-login {
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
        }

        .btn-login:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 167, 142, 0.4);
        }

        /* --- CSS CHO NÚT GOOGLE --- */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #aaa;
            font-size: 0.85rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #eee;
        }
        .divider:not(:empty)::before {
            margin-right: .5em;
        }
        .divider:not(:empty)::after {
            margin-left: .5em;
        }

        .btn-google {
            background-color: white;
            border: 1px solid #ddd;
            color: #555;
            height: 50px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
        }

        .btn-google:hover {
            background-color: #f8f9fa;
            border-color: #ccc;
            transform: translateY(-1px);
        }
        
        .btn-google img {
            width: 20px;
            height: 20px;
        }

        .register-link {
            margin-top: 25px;
            font-size: 0.9rem;
            color: #666;
        }
        .register-link a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
        .alert { border-radius: 10px; font-size: 0.9rem; }
    </style>
</head>
<body class="login-page"> 
    
    <div class="login-card">
        <div class="card-body login-card-body">
            
            <div class="brand-logo">
                <i class="fas fa-city"></i> SmartCity
            </div>
            <p class="login-title">Chào mừng quay trở lại!</p>

            <form action="login_action.php" method="POST">
                
                <div class="input-group-custom">
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Nhập email của bạn">
                    <i class="fas fa-envelope input-icon"></i>
                </div>

                <div class="input-group-custom mb-2"> <input type="password" class="form-control" id="password" name="password" required placeholder="Nhập mật khẩu">
                    <i class="fas fa-lock input-icon"></i>
                </div>
                
                <div class="d-flex justify-content-end mb-4">
                    <a href="forgot_password.php" class="text-decoration-none small" style="color: var(--primary-color); font-weight: 600;">
                        Quên mật khẩu?
                    </a>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
                    </button>
                </div>

            </form>

            <div class="divider">Hoặc đăng nhập bằng</div>

            <a href="<?php echo $google_login_url; ?>" class="btn btn-google">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48">
                    <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"></path>
                    <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"></path>
                    <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"></path>
                    <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"></path>
                </svg>
                Google
            </a>
            <div class="text-center register-link">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </div>

        </div>
    </div>
    <script>
    // Tìm form đăng nhập
    const loginForm = document.querySelector('form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Tìm nút submit
            const btn = this.querySelector('button[type="submit"]');
            
            // Đổi nội dung nút thành icon quay vòng tròn
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang xử lý...';
            
            // Làm mờ nút và chặn bấm tiếp
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
        });
    }
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'includes/sweetalert.php'; ?>
</body>
</html>