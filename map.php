<?php
// 1. Include header
include 'header.php'; 

// 2. Bảo vệ trang
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<style>
    /* 1. Tinh chỉnh Layout chính để không bị thanh cuộn thừa */
    body {
        overflow-y: hidden; /* Ẩn thanh cuộn trang chính để map full màn hình */
    }
    .main-layout {
        padding: 0 !important;
        margin: 0 !important;
        height: calc(100vh - 56px); /* Trừ đi chiều cao header (khoảng 56px) */
    }
    .row-full-height {
        height: 100%;
        margin: 0;
    }

    /* 2. Sidebar hiện đại */
    .modern-sidebar {
        background-color: #fff;
        border-right: 1px solid #f0f0f0;
        height: 100%;
        padding: 20px 15px;
        box-shadow: 2px 0 10px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
    }

    .nav-sidebar .nav-link {
        color: #5f6368;
        font-weight: 500;
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 8px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
    }
    
    .nav-sidebar .nav-link i {
        margin-right: 12px;
        font-size: 1.1rem;
    }

    /* Hiệu ứng khi di chuột và khi đang chọn (Active) */
    .nav-sidebar .nav-link:hover {
        background-color: #f8f9fa;
        color: #00A693;
    }
    
    .nav-sidebar .nav-link.active {
        background-color: #E6FFFA; /* Màu nền xanh rất nhạt */
        color: #00A693; /* Màu chữ xanh Teal */
        font-weight: 600;
    }

    /* Footer nhỏ ở sidebar */
    .sidebar-footer {
        margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    /* 3. Khu vực Bản đồ */
    .map-wrapper {
        position: relative; /* Để đặt bộ lọc trôi lên trên */
        padding: 0 !important;
        height: 100%;
    }

    #full-map-container {
        width: 100%;
        height: 100% !important;
        z-index: 1;
    }

    /* 4. BỘ LỌC TRÔI (FLOATING FILTER) - GLASSMORPHISM */
    .floating-filter-card {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 300px;
        background: rgba(255, 255, 255, 0.95); /* Nền trắng trong suốt nhẹ */
        backdrop-filter: blur(10px); /* Hiệu ứng làm mờ nền sau kính */
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); /* Đổ bóng mềm */
        border: 1px solid rgba(255, 255, 255, 0.5);
        z-index: 1000; /* Luôn nằm trên bản đồ */
        transition: transform 0.3s ease;
    }

    .floating-filter-card:hover {
        transform: translateY(-2px);
    }

    .filter-title {
        font-size: 1rem;
        font-weight: 700;
        color: #00A693;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-select-custom {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        padding: 10px;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }
    
    .form-select-custom:focus {
        border-color: #00A693;
        box-shadow: 0 0 0 0.2rem rgba(0, 166, 147, 0.25);
    }

</style>

<div class="container-fluid main-layout">
    <div class="row row-full-height">

        <div class="col-lg-2 d-none d-lg-block p-0">
            <div class="modern-sidebar">
                <p class="text-muted small text-uppercase fw-bold mb-3 ps-2">Menu</p>
                
                <ul class="nav nav-pills flex-column nav-sidebar">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home fa-fw"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="map.php">
                            <i class="fas fa-map-marked-alt fa-fw"></i> Bản đồ sự cố
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user fa-fw"></i> Cá nhân
                        </a>
                    </li>
                </ul>

                <div class="sidebar-footer">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt fa-fw"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-10 col-md-12 map-wrapper">
            
            <div id="full-map-container"></div>

            <div class="floating-filter-card">
                <div class="filter-title">
                    <i class="fas fa-filter"></i> Bộ lọc hiển thị
                </div>
                
                <div class="mb-2">
                    <label class="small text-muted mb-1">Trạng thái sự cố</label>
                    <select class="form-select form-select-custom" id="filter-status">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="pending">Chờ xử lý</option>
                        <option value="processing">Đang xử lý</option>
                        <option value="resolved">Đã khắc phục</option>
                    </select>
                </div>

                <div class="mb-0">
                    <label class="small text-muted mb-1">Danh mục</label>
                    <select class="form-select form-select-custom" id="filter-category">
                        <option value="all">Tất cả danh mục</option>
                        <?php
                        // Kiểm tra biến kết nối CSDL $pdo (được tạo trong header.php -> db_connect.php)
                        if (isset($pdo)) {
                            try {
                                // Lấy id và tên danh mục, sắp xếp theo tên
                                $stmt_cat = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
                                while ($row_cat = $stmt_cat->fetch()) {
                                    echo '<option value="' . $row_cat['id'] . '">' . htmlspecialchars($row_cat['name']) . '</option>';
                                }
                            } catch (Exception $e) {
                                // Nếu lỗi thì thôi, không hiển thị gì thêm
                                error_log("Map Category Error: " . $e->getMessage());
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

        </div>
        
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?>