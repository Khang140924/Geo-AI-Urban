<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../db_connect.php'; // Đi ra 2 cấp
require_once '../security_check.php'; // Kiểm tra admin

$report_id = $_GET['id'] ?? 0;

if (!$report_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID báo cáo.']);
    exit;
}

try {
    $result = [];

    // --- 1. Lấy thông tin cơ bản ---
    // SỬA LỖI: JOIN u.id -> u.id (Giả định id là đúng, vì user_id là của report)
    $sql_report = "
        SELECT 
            r.*, 
            u.fullname AS user_name, 
            c.name AS category_name
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN categories c ON r.category_id = c.id
        WHERE r.id = :rid
    ";
    $stmt_report = $pdo->prepare($sql_report);
    $stmt_report->execute([':rid' => $report_id]);
    $result['details'] = $stmt_report->fetch(PDO::FETCH_ASSOC);

    if (!$result['details']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy báo cáo.']);
        exit;
    }

    // --- 2. Lấy lịch sử phân công ---
    // SỬA LỖI: JOIN admin.id -> admin.id (Giả định ID admin cũng là 'id')
    $sql_history = "
        SELECT 
            ra.note,
            ra.assigned_at AS assigned_at,
            au.name AS unit_name,
            admin.fullname AS admin_name
        FROM report_assignments ra
        LEFT JOIN assigned_units au ON ra.unit_id = au.id
        LEFT JOIN users admin ON ra.assigned_by = admin.id 
        WHERE ra.report_id = :rid
        ORDER BY ra.assigned_at DESC
    ";
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([':rid' => $report_id]);
    $result['history'] = $stmt_history->fetchAll(PDO::FETCH_ASSOC);


    // --- 3. Lấy toàn bộ bình luận ---
    // SỬA LỖI: JOIN u.id -> u.id
    $sql_comments = "
        SELECT 
            c.comment_text, 
            c.created_at AS comment_at,
            u.fullname AS user_name,
            u.avatar_url
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.report_id = :rid
        ORDER BY c.created_at ASC
    ";
    $stmt_comments = $pdo->prepare($sql_comments);
    $stmt_comments->execute([':rid' => $report_id]);
    $result['comments'] = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);


    // --- 4. Trả về JSON (Đã sửa lỗi ký tự) ---
    $result = mb_convert_encoding($result, 'UTF-8', 'UTF-8');
    $json_data = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Lỗi khi mã hóa JSON: ' . json_last_error_msg());
    }
    echo $json_data;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>