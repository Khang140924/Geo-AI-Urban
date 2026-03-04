<?php
session_start();
require_once 'db_connect.php';

// --- CẤU HÌNH GOOGLE (BẠN DÁN MÃ VỪA LẤY VÀO ĐÂY) ---
$client_id = 'YOUR_GOOGLE_CLIENT_ID';
$client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirect_uri = 'http://localhost/DOAN_CN/login_google_action.php'; 
// ----------------------------------------------------

// 1. Chuyển hướng sang Google nếu chưa có code
if (!isset($_GET['code'])) {
    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';
    $params = [
        'response_type' => 'code',
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'email profile',
        'access_type' => 'online'
    ];
    header('Location: ' . $auth_url . '?' . http_build_query($params));
    exit;
}

// 2. Nếu có code, đổi code lấy Token
if (isset($_GET['code'])) {
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'code' => $_GET['code'],
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bỏ comment dòng này nếu lỗi SSL
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (!isset($token_data['access_token'])) {
        die('Lỗi: Không lấy được Token từ Google. Kiểm tra lại Client ID/Secret.');
    }

    // 3. Lấy thông tin người dùng từ Google
    $user_info_url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $token_data['access_token'];
    $user_info = json_decode(file_get_contents($user_info_url), true);

    if (isset($user_info['email'])) {
        $email = $user_info['email'];
        $fullname = $user_info['name'];
        $google_id = $user_info['id'];
        $avatar = $user_info['picture'];

        try {
            // 4. Kiểm tra user trong CSDL
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if ($user['is_active'] == 0) {
                    $stmt_active = $pdo->prepare("UPDATE users SET is_active = 1, verification_code = NULL WHERE id = ?");
                    $stmt_active->execute([$user['id']]);
                }
                // A. Đã có tài khoản -> Đăng nhập
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_fullname'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'];
                
                // Cập nhật avatar Google nếu muốn (tùy chọn)
                if (empty($user['avatar_url'])) {
                     $upd = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                     $upd->execute([$avatar, $user['id']]);
                     $_SESSION['user_avatar_url'] = $avatar;
                } else {
                     $_SESSION['user_avatar_url'] = $user['avatar_url'];
                }

            } else {
                // B. Chưa có -> Tự động Đăng ký
                $sql = "INSERT INTO users (fullname, email, password_hash, role, avatar_url, phone_number, is_active, created_at) 
                        VALUES (?, ?, ?, 'user', ?, '', 1, GETDATE())";
                
                // Tạo mật khẩu ngẫu nhiên
                $dummy_password = password_hash(uniqid() . time(), PASSWORD_DEFAULT); 
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fullname, $email, $dummy_password, $avatar]);
                
                // Đăng nhập luôn sau khi tạo
                $new_user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['user_fullname'] = $fullname;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_avatar_url'] = $avatar;
            }

            // Xong xuôi, về trang chủ
            header('Location: index.php');
            exit;

        } catch (Exception $e) {
            die("Lỗi CSDL: " . $e->getMessage());
        }
    } else {
        die('Lỗi: Không lấy được thông tin email.');
    }
}
?>