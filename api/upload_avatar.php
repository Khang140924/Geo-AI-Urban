<?php
require_once '../db_connect.php'; // Đã bao gồm session_start()
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}

// Kiểm tra xem file đã được gửi chưa
if (!isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'message' => 'Không có file nào được chọn.']);
    exit;
}

$file = $_FILES['avatar'];
$user_id = $_SESSION['user_id'];

// Kiểm tra lỗi upload cơ bản
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tải file lên.']);
    exit;
}

// Kiểm tra kích thước (ví dụ: 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File quá lớn, vui lòng chọn file dưới 5MB.']);
    exit;
}

// Kiểm tra loại file (chỉ cho phép ảnh)
$mime_type = mime_content_type($file['tmp_name']);
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ hỗ trợ file JPG, PNG, GIF.']);
    exit;
}

// Tạo thư mục nếu chưa tồn tại
// Đường dẫn từ file api/upload_avatar.php đi ra thư mục gốc rồi vào uploads/
$upload_dir = '../uploads/avatars/'; 
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Tạo tên file duy nhất
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
$destination = $upload_dir . $new_filename;
$db_path = 'uploads/avatars/' . $new_filename; // Đường dẫn lưu vào CSDL (không có ../)

// Di chuyển file
if (move_uploaded_file($file['tmp_name'], $destination)) {
    try {
        // Cập nhật CSDL
        // Dùng cú pháp SQL chuẩn (hoạt động trên cả MySQL và SQL Server)
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$db_path, $user_id]);

        // Cập nhật session
        $_SESSION['user_avatar_url'] = $db_path;

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật ảnh đại diện thành công!',
            'new_avatar_url' => $db_path
        ]);

    } catch (Exception $e) {
        // Nếu thất bại, xóa file vừa upload
        unlink($destination);
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu file.']);
}
?>