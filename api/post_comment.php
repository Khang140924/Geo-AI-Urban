<?php
header('Content-Type: application/json');
require_once '../db_connect.php'; // Đi ra 1 cấp

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Cần đăng nhập để bình luận']));
}

// Lấy dữ liệu JSON gửi lên
$data = json_decode(file_get_contents('php://input'), true);

$report_id = $data['report_id'] ?? null;
$comment_text = trim($data['comment_text'] ?? '');
$user_id = $_SESSION['user_id'];
$user_fullname = $_SESSION['user_fullname']; // Lấy tên từ session để trả về

// === NÂNG CẤP: Lấy avatar_url từ Session ===
$user_avatar_url = $_SESSION['user_avatar_url'] ?? null;
// === KẾT THÚC NÂNG CẤP ===

if (empty($report_id) || empty($comment_text)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Nội dung bình luận không được rỗng']));
}

try {
    // Chèn bình luận mới vào CSDL
    $query = "INSERT INTO comments (user_id, report_id, comment_text) VALUES (:user_id, :report_id, :comment_text)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':report_id' => $report_id,
        ':comment_text' => $comment_text
    ]);
    
    $new_comment_id = $pdo->lastInsertId();
    
    // Trả về thông tin bình luận mới để JS render ngay lập tức
    $new_comment = [
        'id' => $new_comment_id,
        'comment_text' => $comment_text,
        'created_at' => ['date' => date('Y-m-d H:i:s.v')], // Giả lập format ngày tháng
        'user_fullname' => $user_fullname,
        // === NÂNG CẤP: Thêm avatar_url vào phản hồi ===
        'avatar_url' => $user_avatar_url
    ];

    echo json_encode(['success' => true, 'comment' => $new_comment]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Post Comment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi gửi bình luận']);
}
?>