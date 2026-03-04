<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../db_connect.php'); // Đi ra 2 cấp
require_once('../security_check.php'); // Kiểm tra quyền admin

// 1. Kiểm tra quyền Admin và phương thức POST
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $report_id = $data['report_id'];

    if (empty($report_id)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID báo cáo']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Bước 1: Xóa các bình luận (comments) liên quan trước
        $stmt_comments = $pdo->prepare("DELETE FROM comments WHERE report_id = ?");
        $stmt_comments->execute([$report_id]);

        // Bước 2: Xóa các lượt thích (likes) liên quan trước
        $stmt_likes = $pdo->prepare("DELETE FROM likes WHERE report_id = ?");
        $stmt_likes->execute([$report_id]);

        // (Thêm code xóa các bảng liên quan khác nếu có)

        // Bước 3: Xóa báo cáo chính
        $stmt_report = $pdo->prepare("DELETE FROM reports WHERE id = ?");
        $stmt_report->execute([$report_id]);

        $pdo->commit();
        
        if ($stmt_report->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa báo cáo và các dữ liệu liên quan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy báo cáo để xóa.']);
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc không có quyền admin.']);
}
?>