<?php
header('Content-Type: application/json');
require_once '../db_connect.php'; // Đi ra 1 cấp

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Cần đăng nhập']));
}

// Lấy report_id từ URL
$report_id = $_GET['report_id'] ?? null;
if (empty($report_id) || !is_numeric($report_id)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Thiếu ID báo cáo']));
}

try {
    // === NÂNG CẤP: Thêm u.avatar_url vào câu SELECT ===
    $query = "
        SELECT 
            c.id, 
            c.comment_text, 
            c.created_at,
            u.fullname AS user_fullname,
            u.avatar_url -- Lấy avatar người bình luận
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.report_id = :report_id
        ORDER BY c.created_at DESC -- Hiển thị bình luận mới nhất trước
    ";
    // === KẾT THÚC NÂNG CẤP ===

    $stmt = $pdo->prepare($query);
    $stmt->execute([':report_id' => $report_id]);
    $comments = $stmt->fetchAll();
    
    // Trả về JSON
    echo json_encode(['success' => true, 'comments' => $comments]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Get Comments Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi tải bình luận']);
}
?>