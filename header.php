<?php 
require_once('db_connect.php'); // File này đã có session_start() và $pdo

// --- BỔ SUNG: LẤY THÔNG TIN USER KHI TẢI TRANG ---
// === SỬA Ở ĐÂY: Xóa điều kiện !isset($_SESSION['user_avatar_url']) ===
// Để nó luôn chạy và kiểm tra CSDL mỗi khi tải trang
if (isset($_SESSION['user_id'])) {
    
    if (isset($pdo)) {
        try {
            // === SỬA Ở ĐÂY: Thêm "is_active" vào câu SELECT ===
            $stmt = $pdo->prepare("SELECT fullname, email, role, avatar_url, is_active FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                
                // === THÊM Ở ĐÂY: Kiểm tra trạng thái is_active trước ===
                if ($user['is_active'] != 1) {
                    // User đã bị khóa! -> Hủy session và đuổi về login
                    session_unset();
                    session_destroy();
                    header("Location: login.php?error=Tài khoản của bạn đã bị khóa.");
                    exit;
                }
                
                // --- Code cũ của bạn bắt đầu từ đây (giữ nguyên) ---
                // Lưu tất cả thông tin vào Session
                $_SESSION['user_fullname'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Xử lý avatar: Nếu rỗng thì dùng avatar mặc định
                if (!empty($user['avatar_url'])) {
                    $_SESSION['user_avatar_url'] = $user['avatar_url'];
                } else {
                    // Dùng ui-avatars thay vì file tĩnh
                    $_SESSION['user_avatar_url'] = "https://ui-avatars.com/api/?name=" . urlencode($user['fullname']) . "&background=00A78E&color=fff&size=32";
                }
            } else {
                // User không tồn tại (bị xóa?), hủy session và đá về login
                session_unset();
                session_destroy();
                header("Location: login.php");
                exit;
            }
        } catch (Exception $e) {
            // Nếu có lỗi CSDL, ghi log và đá ra
            error_log("Header user load error: " . $e->getMessage());
            // An toàn nhất là hủy session nếu CSDL lỗi
            session_unset();
            session_destroy();
            header("Location: login.php?error=db_error");
            exit;
        }
    }
}

// --- KHÔNG CẮT: Giữ nguyên code fallback của bạn ---
// Đặt avatar mặc định nếu vì lý do nào đó session vẫn trống
if (!isset($_SESSION['user_avatar_url']) && isset($_SESSION['user_fullname'])) {
     $_SESSION['user_avatar_url'] = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['user_fullname']) . "&background=00A78E&color=fff&size=32";
} elseif (!isset($_SESSION['user_avatar_url'])) {
    $_SESSION['user_avatar_url'] = "https://ui-avatars.com/api/?name=User&background=00A78E&color=fff&size=32"; // Fallback cuối cùng
}

if (!isset($_SESSION['user_fullname'])) {
    $_SESSION['user_fullname'] = "Khách";
}
// --- KẾT THÚC BỔ SUNG ---
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Phản hồi Đô thị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3/dist/geosearch.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #00A693;">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-city"></i> SmartCity
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Trang chủ</a>
                </li>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>

                    <li class="nav-item dropdown" id="notification-dropdown-container">
                        <a class="nav-link" href="#" id="notification-bell" role="button" data-bs-toggle="dropdown" aria-expanded="false" 
                           style="position: relative; margin-right: 5px;">
                            <i class="fas fa-bell"></i>
                            <span class="badge rounded-pill bg-danger" id="notification-count" 
                                  style="position: absolute; top: 5px; right: -5px; font-size: 0.6em; display: none;">
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" id="notification-list" aria-labelledby="notification-bell">
                            <li><p class="text-center p-3 text-muted mb-0">Đang tải...</p></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar_url']); ?>" alt="Avatar" id="header-avatar" class="nav-avatar-img me-2">
                            Chào, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_fullname'])[0]); // Chỉ lấy tên đầu ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Quản lý cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="register.php">Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
             </div>
    </div>
</nav>