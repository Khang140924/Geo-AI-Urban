<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php'; // Đi ra 1 cấp

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Cần đăng nhập']));
}

try {
    // Lấy tất cả các cột cần thiết để hiển thị trên popup của bản đồ
    $query = "
        SELECT 
            r.id, 
            r.description, 
            r.image_url, 
            r.latitude, 
            r.longitude, 
            r.status,
            c.name AS category_name
        FROM reports r
        JOIN categories c ON r.category_id = c.id
        -- (Tùy chọn) Bạn có thể bỏ lọc WHERE này nếu muốn hiển thị cả báo cáo đã hoàn thành
       WHERE r.status != N'Chờ duyệt' -- Chỉ hiển thị các báo cáo đã được duyệt
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $pdo->query($query);
    $reports = $stmt->fetchAll();
    
    // Trả về JSON chuẩn
    echo json_encode(['success' => true, 'reports' => $reports]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Get All Reports Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi tải dữ liệu bản đồ.']);
}
?>