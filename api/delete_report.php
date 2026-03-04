<?php
// BẮT ĐẦU: KHAI BÁO VÀ KIỂM TRA
header('Content-Type: application/json'); 
require_once '../db_connect.php'; 

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. LẤY DỮ LIỆU TỪ JSON INPUT (CÁCH ĐÚNG)
$input_json = file_get_contents('php://input');
$data = json_decode($input_json, true);

$report_id = $data['report_id'] ?? null;

// 3. Validation
if (empty($report_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID báo cáo.']);
    exit;
}

// BẮT ĐẦU KHỐI TRY/CATCH
try {
    // BẮT ĐẦU TRANSACTION
    $pdo->beginTransaction();

    // 4. XÓA DỮ LIỆU LIÊN QUAN (LIKES VÀ COMMENTS)
    
    // Xóa Likes
    $delete_likes = $pdo->prepare("DELETE FROM likes WHERE report_id = :report_id");
    $delete_likes->execute([':report_id' => $report_id]);

    // Xóa Comments
    $delete_comments = $pdo->prepare("DELETE FROM comments WHERE report_id = :report_id");
    $delete_comments->execute([':report_id' => $report_id]);

    // 5. THỰC HIỆN XÓA BÁO CÁO (KÈM KIỂM TRA QUYỀN SỞ HỮU)

    $delete_sql = "DELETE FROM reports WHERE id = :report_id AND user_id = :user_id";
    $delete_stmt = $pdo->prepare($delete_sql);
    $delete_stmt->execute([
        ':report_id' => $report_id, 
        ':user_id' => $user_id
    ]);

    // Kiểm tra xem có bài viết nào được xóa không
    if ($delete_stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa báo cáo này hoặc báo cáo không tồn tại.']);
        exit;
    }

    // KẾT THÚC TRANSACTION
    $pdo->commit();

    // 6. Trả về thành công
    echo json_encode(['success' => true, 'message' => 'Đã xóa báo cáo thành công.']);

} catch (Exception $e) {
    // Xử lý lỗi (Fatal Error)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    
    // LẤY LỖI CSDL CHI TIẾT
    $pdoError = $pdo->errorInfo();
    $errorMessage = "Lỗi CSDL: " . ($pdoError[2] ?? $e->getMessage());
    
    // In thông báo lỗi chi tiết ra console (không phải alert) và log
    error_log("Delete report error: " . $errorMessage);
    
    // Vẫn trả về lỗi chung cho người dùng cuối nếu không thể lấy lỗi chi tiết
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi xóa báo cáo. Vui lòng kiểm tra console để biết chi tiết.']);
}
?>