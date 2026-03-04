<?php
header('Content-Type: application/json');
require_once '../../db_connect.php';

// 1. Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập.']);
    exit;
}

// 2. Lấy dữ liệu từ POST
$report_id = $_POST['edit_report_id'] ?? null;
$description = trim($_POST['edit_description'] ?? '');
$category_id = $_POST['edit_category_id'] ?? null;
$user_id = $_SESSION['user_id'];

// 3. Validation
if (empty($report_id) || empty($description) || empty($category_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.']);
    exit;
}

try {
    // 4. KIỂM TRA QUYỀN SỞ HỮU và trạng thái (nếu cần)
    $check_sql = "SELECT id, status FROM reports WHERE id = :report_id AND user_id = :user_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':report_id' => $report_id, ':user_id' => $user_id]);
    $report = $check_stmt->fetch();

    if (!$report) {
        http_response_code(403); // Forbidden or Not Found
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa báo cáo này.']);
        exit;
    }

    // (Tùy chọn) Chỉ cho sửa khi là 'Mới'
    // if ($report['status'] !== 'Mới') {
    //     http_response_code(403);
    //     echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể sửa báo cáo khi trạng thái là "Mới".']);
    //     exit;
    // }

    // 5. Thực hiện cập nhật
    $update_sql = "UPDATE reports SET description = :description, category_id = :category_id WHERE id = :report_id AND user_id = :user_id";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([
        ':description' => $description,
        ':category_id' => $category_id,
        ':report_id' => $report_id,
        ':user_id' => $user_id
    ]);

    // 6. Trả về thành công
    echo json_encode(['success' => true, 'message' => 'Cập nhật báo cáo thành công.']);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Update report error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi cập nhật báo cáo.']);
}
?>