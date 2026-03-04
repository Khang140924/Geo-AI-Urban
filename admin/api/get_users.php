<?php
header('Content-Type: application/json');
require_once('../../db_connect.php'); 
require_once('../security_check.php'); // Kiểm tra admin

if ($is_admin) {
    try {
        // Lấy các cột bạn cần từ bảng 'users'
        // Đảm bảo tên cột (fullname, avatar_url, is_active) khớp 100%
        $sql = "SELECT id, fullname, email, role, avatar_url, is_active, verification_code 
            FROM users 
            WHERE is_active = 1 
            OR verification_code IS NULL";
        
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'users' => $users]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Truy cập bị từ chối. Bạn không phải là admin.']);
}
?>