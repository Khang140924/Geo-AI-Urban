<?php
header('Content-Type: application/json');
require_once('../../db_connect.php');
require_once('../security_check.php');

if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $data['user_id'];
    $new_status = $data['new_status']; // Sẽ là 0 (Khóa) hoặc 1 (Mở)

    // Đảm bảo new_status là 0 hoặc 1
    if ($new_status !== 0 && $new_status !== 1) {
         echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
         exit;
    }

    try {
        // Cập nhật cột 'is_active'
        $sql = "UPDATE users SET is_active = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['status' => $new_status, 'id' => $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc không có quyền admin.']);
}
?>