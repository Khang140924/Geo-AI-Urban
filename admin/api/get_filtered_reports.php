<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../db_connect.php'; // Đi ra 2 cấp
require_once '../security_check.php'; // Kiểm tra admin

try {
    // --- CÀI ĐẶT PHÂN TRANG ---
    $reports_per_page = 5; // Số báo cáo mỗi trang

    // 1. Lấy các biến
    $category_id = $_GET['category_id'] ?? 'all';
    $time_range = $_GET['time_range'] ?? 'all';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $reports_per_page;

    // 2. Xây dựng mệnh đề WHERE (để dùng cho cả 2 truy vấn)
    $params = [];
    $where_clauses = [];

    // Lọc theo Danh mục
    if ($category_id != 'all') {
        $where_clauses[] = "r.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    // Lọc theo Thời gian (SQL Server syntax)
    if ($time_range == '7') {
        $where_clauses[] = "r.created_at >= DATEADD(day, -7, GETDATE())";
    } elseif ($time_range == '30') {
        $where_clauses[] = "r.created_at >= DATEADD(month, -1, GETDATE())";
    } elseif ($time_range == 'today') {
        $where_clauses[] = "r.created_at >= CAST(GETDATE() AS DATE)";
    }
    
    $where_sql = "";
    if (count($where_clauses) > 0) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    }

    // 3. --- MỚI: TRUY VẤN ĐẾM (COUNT) ---
    // Cần JOIN nếu WHERE có điều kiện từ bảng khác, nhưng ở đây ko cần
    $sql_count = "SELECT COUNT(*) FROM reports r" . $where_sql;
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_reports = (int) $stmt_count->fetchColumn();
    $total_pages = ceil($total_reports / $reports_per_page);


    // 4. --- SỬA: TRUY VẤN LẤY DỮ LIỆU (CÓ PHÂN TRANG) ---
    $sql_data = "
        SELECT 
            r.id, r.description, r.image_url, r.status, r.created_at,
            u.fullname AS user_name,
            c.name AS category_name,
            latest_assignment.unit_name AS assigned_unit_name -- MỚI: Lấy từ OUTER APPLY

        FROM reports r
        JOIN users u ON r.user_id = u.id
        JOIN categories c ON r.category_id = c.id
        
        -- MỚI: Dùng OUTER APPLY để lấy tên đơn vị mới nhất
        -- Nó sẽ chạy 1 lần cho mỗi dòng 'r' (reports)
        OUTER APPLY (
            SELECT TOP 1 au.name AS unit_name
            FROM report_assignments ra
            JOIN assigned_units au ON ra.unit_id = au.id
            WHERE ra.report_id = r.id
            ORDER BY ra.id DESC -- Giả định id là khóa tự tăng, lấy cái mới nhất
        ) AS latest_assignment

        " . $where_sql . " -- Áp dụng bộ lọc
        
        ORDER BY r.created_at DESC
        
        -- Cú pháp phân trang của SQL Server --
        OFFSET :offset ROWS 
        FETCH NEXT :limit ROWS ONLY
    ";

    // Thêm params cho phân trang (phải là kiểu INT)
    $params_data = $params; // Copy
    $params_data[':offset'] = $offset;
    $params_data[':limit'] = $reports_per_page;

    $stmt_data = $pdo->prepare($sql_data);
    
    // Bind các tham số phân trang
    $stmt_data->bindParam(':offset', $params_data[':offset'], PDO::PARAM_INT);
    $stmt_data->bindParam(':limit', $params_data[':limit'], PDO::PARAM_INT);
    
    // Bind các tham số WHERE (nếu có)
    if (isset($params_data[':category_id'])) {
        $stmt_data->bindParam(':category_id', $params_data[':category_id']);
    }

    $stmt_data->execute(); 
    $reports = $stmt_data->fetchAll();

    // 5. --- MỚI: Trả về JSON có cấu trúc ---
    echo json_encode([
        'reports' => $reports,
        'total_pages' => $total_pages,
        'current_page' => $page
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE); 
}
?>