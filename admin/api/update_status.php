<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../db_connect.php'; // Đã có session_start()
require_once '../security_check.php'; // Kiểm tra quyền Admin

// 1. Kiểm tra quyền Admin và phương thức POST
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. Lấy dữ liệu từ POST
    $report_id = $_POST['report_id'];
    $newStatus = $_POST['status'];

    if (empty($report_id) || empty($newStatus)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Thiếu ID báo cáo hoặc trạng thái mới.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 3. Cập nhật trạng thái của báo cáo
        $stmt_update = $pdo->prepare("UPDATE reports SET status = :status WHERE id = :id");
        $stmt_update->execute(['status' => $newStatus, 'id' => $report_id]);

        // 4. === NÂNG CẤP: Lấy user_id VÀ description của báo cáo ===
        $stmt_get_report = $pdo->prepare("SELECT user_id, description FROM reports WHERE id = ?");
        $stmt_get_report->execute([$report_id]);
        $report_data = $stmt_get_report->fetch(PDO::FETCH_ASSOC);
        
        $report_owner_id = $report_data['user_id'];
        
        // Lấy 50 ký tự đầu của mô tả để làm tiêu đề
        $report_desc_short = mb_substr($report_data['description'], 0, 50, 'UTF-8');
        if (mb_strlen($report_data['description'], 'UTF-8') > 50) {
            $report_desc_short .= '...'; // Thêm dấu ...
        }

        $message = null;
        $link = "../profile.php#report-card-" . $report_id; 

        // 5. === NÂNG CẤP: Quyết định nội dung thông báo (thêm chi tiết) ===
        if ($newStatus == 'Mới') {
            $message = "Báo cáo '" . $report_desc_short . "' của bạn đã được duyệt.";
        } else if ($newStatus == 'Đang xử lý') {
            $message = "Báo cáo '" . $report_desc_short . "' đang được xử lý.";
        } else if ($newStatus == 'Đã hoàn thành') {
            $message = "Sự cố '" . $report_desc_short . "' bạn báo cáo đã được xử lý xong.";
        } else if ($newStatus == 'Không hợp lệ') {
            $message = "Báo cáo '" . $report_desc_short . "' đã bị từ chối (không hợp lệ).";
        }

        // 6. Nếu có nội dung thông báo VÀ tìm thấy chủ sở hữu
        if ($message && $report_owner_id) {
            $sql_notify = "
                INSERT INTO notifications (user_id, report_id, message, link) 
                VALUES (?, ?, ?, ?)
            ";
            $stmt_notify = $pdo->prepare($sql_notify);
            $stmt_notify->execute([$report_owner_id, $report_id, $message, $link]);
        }

        // 7. Hoàn tất Transaction
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công và đã gửi thông báo.']);

    } catch (PDOException $e) {
        $pdo->rollBack(); // Hoàn tác nếu có lỗi
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
    }
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền hoặc yêu cầu không hợp lệ.']);
}
?>