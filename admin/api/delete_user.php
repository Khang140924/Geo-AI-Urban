<?php
header('Content-Type: application/json');
require_once('../../db_connect.php');
require_once('../security_check.php');

if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'];

    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
        exit;
    }

    try {
        // --- QUAN TRỌNG: PHẢI XÓA CÁC BẢNG PHỤ TRƯỚC ---
        
        // 1. Xóa THÔNG BÁO của người này (Đây là nguyên nhân gây lỗi bạn vừa gặp)
        $stmt_notif = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt_notif->execute([$user_id]);

        // 2. Xóa LƯỢT THÍCH của người này (Nếu không xóa sẽ lỗi tiếp)
        // (Lưu ý: Nếu bảng của bạn tên là 'report_likes' thì sửa chữ 'likes' bên dưới thành 'report_likes')
        $stmt_likes = $pdo->prepare("DELETE FROM likes WHERE user_id = ?");
        $stmt_likes->execute([$user_id]);

        // 3. Xóa BÌNH LUẬN của người này
        $stmt_comments = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
        $stmt_comments->execute([$user_id]);

        // 4. Xóa BÁO CÁO do người này đăng
        // (Lưu ý: Nếu báo cáo có ảnh, tốt nhất là nên xóa file ảnh trong thư mục uploads nữa, nhưng ở đây ta xóa CSDL trước đã)
        $stmt_reports = $pdo->prepare("DELETE FROM reports WHERE user_id = ?");
        $stmt_reports->execute([$user_id]);
        
        // 5. Cuối cùng mới được xóa USER
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa vĩnh viễn người dùng và toàn bộ dữ liệu liên quan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng hoặc đã bị xóa trước đó.']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc không có quyền admin.']);
}
?>