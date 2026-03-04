<?php
require_once '../db_connect.php'; 
header('Content-Type: application/json; charset=utf-8'); 

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// === [THÊM MỚI] === NHẬN BIẾN SẮP XẾP TỪ CLIENT (Mặc định là DESC)
$sort_order = 'DESC';
if (isset($_GET['sort']) && strtoupper($_GET['sort']) === 'ASC') {
    $sort_order = 'ASC';
}
// =================================================================

// 2. Chuẩn bị object JSON để trả về
$response = [
    'success' => true, 
    'reports' => [], 
    'stats' => ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0] 
];

try {
    // 3. Lấy danh sách báo cáo (ĐÃ CẬP NHẬT: LẤY TÊN ĐƠN VỊ)
    $query_reports = "
        SELECT 
            r.id, 
            r.description, 
            r.image_url, 
            r.status, 
            r.created_at,
            r.category_id, 
            c.name AS category_name,
            u.fullname AS author_fullname, 
            u.avatar_url AS author_avatar_url,
            
            -- === MỚI: Lấy tên đơn vị xử lý ===
            au.name AS unit_name,

            (SELECT COUNT(*) FROM likes l WHERE l.report_id = r.id) AS like_count,
            (SELECT COUNT(*) FROM likes l WHERE l.report_id = r.id AND l.user_id = :current_user_id) AS user_has_liked
        
        FROM reports r
        JOIN categories c ON r.category_id = c.id
        JOIN users u ON r.user_id = u.id
        
        -- === MỚI: KẾT NỐI VỚI BẢNG PHÂN CÔNG ===
        LEFT JOIN (
            SELECT report_id, MAX(id) as max_id 
            FROM report_assignments 
            GROUP BY report_id
        ) latest_assign ON r.id = latest_assign.report_id
        LEFT JOIN report_assignments ra ON ra.id = latest_assign.max_id
        LEFT JOIN assigned_units au ON ra.unit_id = au.id
        
        WHERE r.user_id = :user_id_reports
        
        -- === [ĐÃ SỬA] === THAY 'DESC' CỨNG BẰNG BIẾN $sort_order
        ORDER BY r.created_at $sort_order
    ";
    
    $stmt_reports = $pdo->prepare($query_reports);
    $stmt_reports->execute([
        ':current_user_id' => $user_id,
        ':user_id_reports' => $user_id
    ]);
    
    $response['reports'] = $stmt_reports->fetchAll(PDO::FETCH_ASSOC);

    // 4. Lấy số liệu thống kê (Giữ nguyên logic của mày)
    $query_stats = "
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = N'Chờ duyệt' OR status = N'Mới' THEN 1 ELSE 0 END) AS pending, 
            SUM(CASE WHEN status = N'Đang xử lý' THEN 1 ELSE 0 END) AS processing,
            SUM(CASE WHEN status = N'Đã hoàn thành' THEN 1 ELSE 0 END) AS completed
        FROM reports
        WHERE user_id = :user_id_stats
    ";
    
    $stmt_stats = $pdo->prepare($query_stats);
    $stmt_stats->execute([':user_id_stats' => $user_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    if ($stats) {
        $response['stats']['total'] = (int)$stats['total'];
        $response['stats']['pending'] = (int)($stats['pending'] ?? 0); 
        $response['stats']['processing'] = (int)($stats['processing'] ?? 0);
        $response['stats']['completed'] = (int)($stats['completed'] ?? 0);
    }

    // 5. Trả về object JSON đầy đủ
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
    error_log("Get My Reports Error: " . $e->getMessage());
    echo json_encode($response);
}
?>