<?php
// Luôn bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Gán giá trị mặc định
$is_admin = false; 

// --- Code cũ của bạn bắt đầu từ đây ---

if (!isset($_SESSION['user_id'])) {
    // Nếu gọi từ API (không phải trang web), trả về lỗi JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401); // 401 Unauthorized
        echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.']);
        exit;
    } else {
        // Nếu là trang web bình thường, chuyển hướng
        header('Location: ../login.php'); // Quay ra thư mục gốc
        exit;
    }
}

if ($_SESSION['user_role'] != 'admin') {
    // Nếu gọi từ API
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(403); // 403 Forbidden
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
        exit;
    } else {
        // Nếu là trang web
        die('Truy cập bị từ chối. Bạn không có quyền quản trị.');
    }
}

// 2. NẾU VƯỢT QUA HẾT == LÀ ADMIN
// Dòng này là quan trọng nhất!!!
$is_admin = true;

// 3. File này không cần db_connect.php
// Nó chỉ cần kiểm tra SESSION
?>