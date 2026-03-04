<?php
header('Content-Type: application/json');
require_once '../db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// LẤY DỮ LIỆU TỪ JSON INPUT (CÁCH ĐÚNG)
$data = json_decode(file_get_contents('php://input'), true);

$report_id = $data['report_id'] ?? null;
$description = trim($data['description'] ?? '');
$category_id = $data['category_id'] ?? null; 

// SỬA ĐIỀU KIỆN VALIDATION: category_id và report_id phải là số nguyên dương
if (!filter_var($report_id, FILTER_VALIDATE_INT) || !filter_var($category_id, FILTER_VALIDATE_INT) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ. Vui lòng điền đầy đủ thông tin.']);
    exit;
}

try {
    // Thêm trường updated_at = GETDATE() để cập nhật thời gian sửa
    $query = "
    UPDATE reports 
    SET description = :description, category_id = :category_id 
    WHERE id = :report_id AND user_id = :user_id
";
    
    $stmt = $pdo->prepare($query);
    
    // Dùng bindParam với kiểu dữ liệu (cho SQLSRV ổn định hơn)
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa báo cáo này hoặc báo cáo không tồn tại.']);
        exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật báo cáo thành công!']);

} catch (Exception $e) {
    http_response_code(500);
    // TRẢ VỀ LỖI CSDL CHI TIẾT ĐỂ GỠ LỖI
    $pdoError = $pdo->errorInfo();
    $errorMessage = "Lỗi CSDL: " . ($pdoError[2] ?? $e->getMessage());

    echo json_encode(['success' => false, 'message' => $errorMessage]);
    // Ghi log lỗi để kiểm tra thêm
    error_log("Update Report SQL Error: " . $errorMessage); 
}
?>