<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php'; // Đi ra 1 cấp

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Đánh dấu TẤT CẢ thông báo chưa đọc là đã đọc
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true, 'rows_affected' => $stmt->rowCount()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>