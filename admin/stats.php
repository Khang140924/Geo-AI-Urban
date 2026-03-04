<?php
include 'header_admin.php';

// --- PHẦN 1: XỬ LÝ SỐ LIỆU (PHP) ---

// A. Thống kê tổng quan (Giữ nguyên vì đang chạy tốt)
try {
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM reports");
    $total = $stmt_total->fetchColumn();

    $sql_group = "SELECT status, COUNT(*) as num FROM reports GROUP BY status";
    $stmt_group = $pdo->query($sql_group);
    
    $new = 0; $process = 0; $done = 0;

    while ($row = $stmt_group->fetch()) {
        $st = $row['status'];
        $num = $row['num'];
        if (preg_match('/(Mới|New|Pending)/iu', $st)) $new += $num;
        elseif (preg_match('/(Đang|Process|Xử lý)/iu', $st)) $process += $num;
        elseif (preg_match('/(Hoàn thành|Done|Completed|Đã)/iu', $st)) $done += $num;
    }
} catch (Exception $e) { $total=0; $new=0; $process=0; $done=0; }

// B. Biểu đồ Tròn: Tỷ lệ theo Danh mục
try {
    // Kết nối bảng reports với categories để lấy tên
    $sql_cat = "SELECT c.name, COUNT(r.id) as count 
                FROM reports r 
                LEFT JOIN categories c ON r.category_id = c.id 
                GROUP BY c.name";
    $stmt_cat = $pdo->query($sql_cat);
    
    $cat_labels = [];
    $cat_data = [];
    
    while ($row = $stmt_cat->fetch()) {
        // Nếu danh mục bị xóa hoặc null thì đặt tên là "Khác"
        $name = $row['name'] ? $row['name'] : 'Khác';
        $cat_labels[] = $name;
        $cat_data[] = $row['count'];
    }
} catch (Exception $e) { $cat_labels=[]; $cat_data=[]; }

// C. Biểu đồ Cột (Phiên bản dành cho SQL Server)
try {
    // SQL Server dùng TOP thay vì LIMIT
    $sql_trend = "SELECT TOP 50 created_at FROM reports ORDER BY id DESC";
    
    $stmt_trend = $pdo->query($sql_trend);
    $raw_data = $stmt_trend->fetchAll(PDO::FETCH_COLUMN);

    // Xử lý đếm bằng PHP (Giữ nguyên logic cũ)
    $temp_stats = [];
    foreach ($raw_data as $date_str) {
        if (empty($date_str)) continue;

        // SQL Server có thể trả về định dạng ngày hơi lạ, strtotime sẽ xử lý hết
        $time = strtotime($date_str);
        if (!$time) continue; 
        
        $short_date = date('d/m', $time);
        
        if (!isset($temp_stats[$short_date])) {
            $temp_stats[$short_date] = 0;
        }
        $temp_stats[$short_date]++;
    }

    // Đảo ngược để ngày cũ bên trái
    $temp_stats = array_reverse($temp_stats);

    $date_labels = array_keys($temp_stats);
    $date_data = array_values($temp_stats);

} catch (Exception $e) { 
    // Nếu vẫn lỗi thì in ra để biết đường sửa (Mày có thể xóa dòng echo này sau khi chạy được)
    echo "<div style='display:none'>Lỗi cột: ".$e->getMessage()."</div>";
    $date_labels=[]; $date_data=[]; 
}
?>

<div class="container-fluid px-0">
    <h2 class="mb-4 mt-3">Thống kê & Phân tích</h2>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-white p-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                <h3 class="mb-0"><?php echo $total; ?></h3>
                <small>Tổng báo cáo</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white p-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #ff5858, #f09819);">
                <h3 class="mb-0"><?php echo $new; ?></h3>
                <small>Mới tiếp nhận</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white p-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #f6d365, #fda085);">
                <h3 class="mb-0"><?php echo $process; ?></h3>
                <small>Đang xử lý</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white p-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                <h3 class="mb-0"><?php echo $done; ?></h3>
                <small>Đã hoàn thành</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-5"> <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Tỷ lệ theo Danh mục</h5>
                    <div style="height: 300px; display: flex; justify-content: center;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Xu hướng báo cáo (Các ngày có số liệu)</h5>
                    <div style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Dữ liệu từ PHP
    const catLabels = <?php echo json_encode($cat_labels); ?>;
    const catData = <?php echo json_encode($cat_data); ?>;
    const dateLabels = <?php echo json_encode($date_labels); ?>;
    const dateData = <?php echo json_encode($date_data); ?>;

    // 2. Vẽ Biểu đồ Tròn
    const ctxCat = document.getElementById('categoryChart');
    if (catLabels.length > 0) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    } else {
        ctxCat.parentElement.innerHTML = "<p class='text-center text-muted mt-5'>Chưa có dữ liệu danh mục</p>";
    }

    // 3. Vẽ Biểu đồ Cột
    const ctxTrend = document.getElementById('trendChart');
    if (dateLabels.length > 0) {
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: dateLabels,
                datasets: [{
                    label: 'Số lượng báo cáo',
                    data: dateData,
                    backgroundColor: '#00cba9',
                    borderRadius: 5,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    } else {
        ctxTrend.parentElement.innerHTML = "<p class='text-center text-muted mt-5'>Chưa có dữ liệu báo cáo nào</p>";
    }
});
</script>

<?php include 'footer_admin.php'; ?>