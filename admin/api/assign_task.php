<?php
session_start();
header('Content-Type: application/json');
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'] ?? 0;
    $unit_id = $_POST['unit_id'] ?? 0;
    $note = $_POST['note'] ?? '';
    $admin_id = $_SESSION['user_id'] ?? 1; 

    if (!$report_id || !$unit_id) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin!']);
        exit;
    }

    try {
        $pdo->beginTransaction(); // Bắt đầu giao dịch để đảm bảo an toàn

        // 1. Lưu vào bảng phân công
        $sql = "INSERT INTO report_assignments (report_id, unit_id, note, assigned_by) 
                VALUES (:rid, :uid, :note, :aid)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':rid' => $report_id, ':uid' => $unit_id, ':note' => $note, ':aid' => $admin_id]);

        // 2. Cập nhật trạng thái báo cáo -> 'Đang xử lý'
        $sql_update = "UPDATE reports SET status = N'Đang xử lý' WHERE id = :rid";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([':rid' => $report_id]);

        // --- 3. GỬI THÔNG BÁO CHO NGƯỜI DÂN (MỚI) ---
        
        // A. Lấy ID người dân và Tên đơn vị xử lý
        $stmt_info = $pdo->prepare("
            SELECT r.user_id, u.name as unit_name 
            FROM reports r
            JOIN assigned_units u ON u.id = :uid
            WHERE r.id = :rid
        ");
        $stmt_info->execute([':uid' => $unit_id, ':rid' => $report_id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            $user_id = $info['user_id'];
            $unit_name = $info['unit_name'];
            $msg = "Báo cáo của bạn đã được giao cho đơn vị: " . $unit_name;

            // B. Chèn vào bảng notifications
            $sql_notify = "INSERT INTO notifications (user_id, report_id, message) VALUES (:uid, :rid, :msg)";
            $stmt_notify = $pdo->prepare($sql_notify);
            $stmt_notify->execute([':uid' => $user_id, ':rid' => $report_id, ':msg' => $msg]);
        }
        // ---------------------------------------------

        $pdo->commit(); // Chốt đơn
        echo json_encode(['success' => true, 'message' => 'Đã phân công và gửi thông báo!']);

    } catch (Exception $e) {
        $pdo->rollBack(); // Có lỗi thì hoàn tác
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}
?>