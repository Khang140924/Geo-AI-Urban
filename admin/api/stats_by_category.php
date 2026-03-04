<?php
header('Content-Type: application/json');
require_once '../../db_connect.php'; // Đi ra 2 cấp
require_once '../security_check.php'; // Kiểm tra admin

try {
    // 1. Lấy các biến lọc từ GET (nếu có)
    $category_id = $_GET['category_id'] ?? 'all';
    $time_range = $_GET['time_range'] ?? 'all';

    // 2. Xây dựng câu lệnh SQL
    // Chúng ta sẽ dùng mảng `params` để tránh lỗi SQL Injection
    $params = [];
    
    // Câu lệnh SQL cơ bản
    $sql = "
        SELECT 
            c.name AS category_name,
            COUNT(r.id) AS report_count
        FROM reports r
        JOIN categories c ON r.category_id = c.id
    ";

    // 3. Xây dựng mệnh đề WHERE động
    $where_clauses = [];

    // Lọc theo Danh mục
    if ($category_id != 'all') {
        $where_clauses[] = "r.category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    // Lọc theo Thời gian (Dùng cú pháp SQL Server DATEADD)
    if ($time_range == '7') {
        $where_clauses[] = "r.created_at >= DATEADD(day, -7, GETDATE())";
    } elseif ($time_range == '30') {
        $where_clauses[] = "r.created_at >= DATEADD(month, -1, GETDATE())";
    } elseif ($time_range == 'today') {
        // Lấy ngày hôm nay (00:00:00)
        $where_clauses[] = "r.created_at >= CAST(GETDATE() AS DATE)";
    }
    
    // 4. Nối các mệnh đề WHERE
    if (count($where_clauses) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // 5. Thêm GROUP BY và ORDER BY
    $sql .= " GROUP BY c.name ORDER BY report_count DESC";
    
    // 6. Thực thi truy vấn
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params); // Truyền mảng tham số vào
    $data = $stmt->fetchAll();

    // 7. Tách dữ liệu cho Chart.js
    $labels = [];
    $values = [];
    
    foreach ($data as $row) {
        $labels[] = $row['category_name'];
        $values[] = (int)$row['report_count'];
    }

    // 8. Trả về JSON
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>