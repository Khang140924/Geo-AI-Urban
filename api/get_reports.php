<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$category_id = $_GET['category_id'] ?? 'all';

$params = [':current_user_id' => $current_user_id]; 

try {
    // Câu truy vấn SQL (Đã thêm r.category_id)
    $query = "
        SELECT 
            r.id, 
            r.category_id,  -- <<< ĐÃ THÊM CỘT NÀY (QUAN TRỌNG)
            r.description, r.image_url, r.latitude, r.longitude, r.status, r.created_at,
            u.fullname AS author_fullname, 
            u.avatar_url AS author_avatar_url,
            c.name AS category_name,
            
            -- === 1. LẤY THÊM TÊN ĐƠN VỊ XỬ LÝ ===
            au.name AS unit_name, 

            (SELECT COUNT(*) FROM likes l WHERE l.report_id = r.id) AS like_count,
            (SELECT COUNT(*) FROM likes l WHERE l.report_id = r.id AND l.user_id = :current_user_id) AS user_has_liked
        
        FROM reports r
        JOIN users u ON r.user_id = u.id
        JOIN categories c ON r.category_id = c.id
        
        -- === 2. KẾT NỐI VỚI BẢNG PHÂN CÔNG (ĐỂ LẤY ĐƠN VỊ MỚI NHẤT) ===
        LEFT JOIN (
            SELECT report_id, MAX(id) as max_id 
            FROM report_assignments 
            GROUP BY report_id
        ) latest_assign ON r.id = latest_assign.report_id
        LEFT JOIN report_assignments ra ON ra.id = latest_assign.max_id
        LEFT JOIN assigned_units au ON ra.unit_id = au.id

        -- Lọc trạng thái (Bỏ qua tin chưa duyệt)
        WHERE r.status != N'Chờ duyệt' AND r.status != N'Ch? duy?t' 
    ";

    // Lọc theo danh mục (Lọc phía Server)
    if ($category_id !== 'all' && is_numeric($category_id)) {
        $query .= " AND r.category_id = :category_id"; 
        $params[':category_id'] = $category_id;
    }

    // Sắp xếp và Phân trang (SQL Server)
    // Lưu ý: Nếu muốn lấy HẾT lên bản đồ thì bỏ đoạn OFFSET/FETCH đi, 
    // còn nếu chỉ muốn lấy 20 cái mới nhất thì giữ nguyên.
    $query .= " ORDER BY r.created_at DESC OFFSET 0 ROWS FETCH NEXT 100 ROWS ONLY"; 
    // Tao sửa thành 100 rows để lấy nhiều điểm hơn cho bản đồ đẹp

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($reports);

} catch (Exception $e) {
    http_response_code(500);
    // error_log("Get Reports API Error: ". $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi tải báo cáo.']);
}
?>