<?php
// Bật bộ đệm đầu ra để tránh lỗi khoảng trắng
ob_start();

require_once '../db_connect.php'; 

// Xóa sạch mọi nội dung thừa (khoảng trắng, enter, warning) trước đó
ob_clean(); 

header('Content-Type: application/json');

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thích bài viết.']);
    exit;
}

// 2. Lấy dữ liệu
$input = json_decode(file_get_contents('php://input'), true);
$report_id = isset($input['report_id']) ? intval($input['report_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($report_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID bài viết không hợp lệ.']);
    exit;
}

try {
    // Kiểm tra bảng likes (dùng bảng cũ như bạn yêu cầu)
    $checkStmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = :uid AND report_id = :rid");
    $checkStmt->execute([':uid' => $user_id, ':rid' => $report_id]);
    $existingLike = $checkStmt->fetch();

    $userHasLikedNow = false;

    if ($existingLike) {
        // Đã like -> Xóa (Unlike)
        $pdo->prepare("DELETE FROM likes WHERE user_id = :uid AND report_id = :rid")
            ->execute([':uid' => $user_id, ':rid' => $report_id]);
        $userHasLikedNow = false;
    } else {
        // Chưa like -> Thêm mới
        $pdo->prepare("INSERT INTO likes (user_id, report_id) VALUES (:uid, :rid)")
            ->execute([':uid' => $user_id, ':rid' => $report_id]);
        $userHasLikedNow = true;
    }

    // Đếm lại số like
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE report_id = :rid");
    $countStmt->execute([':rid' => $report_id]);
    $newLikeCount = $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'newLikeCount' => $newLikeCount,
        'userHasLiked' => $userHasLikedNow
    ]);

} catch (Exception $e) {
    // Nếu lỗi CSDL (ví dụ chưa có bảng likes), nó sẽ hiện rõ lỗi ra thay vì undefined
    echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . $e->getMessage()]);
}
?>