<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php'; // Đi ra 1 cấp

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 1. Lấy 10 thông báo mới nhất
    $stmt_notifs = $pdo->prepare("
        SELECT TOP 10 * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt_notifs->execute([$user_id]);
    $notifications = $stmt_notifs->fetchAll();

    // 2. Lấy số lượng CHƯA ĐỌC
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt_count->execute([$user_id]);
    $unread_count = $stmt_count->fetchColumn();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unread_count
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>