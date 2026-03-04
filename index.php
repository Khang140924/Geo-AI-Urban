<?php 
// 1. Include header
include 'header.php'; 

// 2. Protect page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- TÍNH TOÁN THỐNG KÊ HÔM NAY (Logic giữ nguyên) ---
$stat_today_new = 0;
$stat_today_processing = 0;
$stat_today_completed = 0;

if (isset($pdo)) {
    try {
        $sql_today = "
            SELECT 
                SUM(CASE WHEN (status = N'Mới' OR status = N'Chờ duyệt') THEN 1 ELSE 0 END) as cnt_new,
                SUM(CASE WHEN status = N'Đang xử lý' THEN 1 ELSE 0 END) as cnt_processing,
                SUM(CASE WHEN (status = N'Đã hoàn thành' OR status = N'Đã xử lý') THEN 1 ELSE 0 END) as cnt_completed
            FROM reports 
            WHERE CAST(created_at AS DATE) = CAST(GETDATE() AS DATE)
        ";
        
        $stmt_stats = $pdo->query($sql_today);
        $result_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

        if ($result_stats) {
            $stat_today_new = (int)$result_stats['cnt_new'];
            $stat_today_processing = (int)$result_stats['cnt_processing'];
            $stat_today_completed = (int)$result_stats['cnt_completed'];
        }
    } catch (Exception $e) {
        error_log("Lỗi thống kê index: " . $e->getMessage());
    }
}

$total_today = $stat_today_new + $stat_today_processing + $stat_today_completed;
$pct_new = $total_today > 0 ? ($stat_today_new / $total_today) * 100 : 0;
$pct_processing = $total_today > 0 ? ($stat_today_processing / $total_today) * 100 : 0;
$pct_completed = $total_today > 0 ? ($stat_today_completed / $total_today) * 100 : 0;
?>

<style>
    /* 1. Hiệu ứng trượt (Sticky) cho 2 cột bên */
    @media (min-width: 992px) {
        .sticky-sidebar {
            position: -webkit-sticky;
            position: sticky;
            top: 90px; /* Cách đỉnh màn hình 90px (trừ hao Header) */
            z-index: 100;
            height: calc(100vh - 100px);
            overflow-y: auto;
            /* Ẩn thanh cuộn cho đẹp */
            scrollbar-width: none; 
        }
        .sticky-sidebar::-webkit-scrollbar { 
            display: none; 
        }
    }

    /* 2. Style cho ô Đăng bài mới (Giống Facebook) */
    .create-report-wrapper {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 15px;
        margin-bottom: 20px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .create-report-wrapper:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .fake-input {
        background-color: #f0f2f5;
        border-radius: 20px;
        padding: 10px 15px;
        color: #65676b;
        font-size: 0.95rem;
    }
    .action-btn {
        padding: 8px 0;
        border-radius: 8px;
        transition: background 0.2s;
        color: #65676b;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .action-btn:hover {
        background-color: #f2f2f2;
        color: #333;
    }
</style>

<div class="container-fluid main-layout">
    <div class="row justify-content-center">

        <div class="col-lg-3 d-none d-lg-block">
            <div class="sidebar-left sticky-sidebar">

                <a href="profile.php" class="sidebar-profile-card">
                    <div class="user-avatar-small">
                        <?php if (!empty($_SESSION['user_avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar_url']); ?>" alt="Avatar">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-fullname-wrapper">
                        <span class="user-fullname"><?php echo htmlspecialchars($_SESSION['user_fullname']); ?></span>
                        <span class="view-profile-link">Xem trang cá nhân</span>
                    </div>
                </a>

                <ul class="nav nav-pills flex-column nav-sidebar">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home fa-fw"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="map.php"> <i class="fas fa-map-marked-alt fa-fw"></i> Bản đồ sự cố
                        </a>
                    </li>

                    <li class="nav-item mt-3 border-top pt-3">
                        <h6 class="sidebar-heading px-3 mb-2 text-muted">Lọc theo danh mục</h6>
                    </li>
                    <?php
    if (isset($pdo)) {
        try {
            $stmt_sidebar_cat = $pdo->query("SELECT MIN(id) as id, name, MIN(icon) as icon FROM categories GROUP BY name ORDER BY name");
            
            while ($cat_sidebar = $stmt_sidebar_cat->fetch()) {
                $cat_icon_sidebar = !empty($cat_sidebar['icon']) ? $cat_sidebar['icon'] : 'fa-tag';
                $cat_name_lower = mb_strtolower($cat_sidebar['name'], 'UTF-8');
                
                // --- [MỚI] LOGIC CHỌN MÀU SẮC ---
                $icon_color = '#00A78E'; // Màu mặc định (Xanh SmartCity)

                if (strpos($cat_name_lower, 'cây') !== false) {
                    $icon_color = '#28a745'; // Xanh lá (Success)
                } elseif (strpos($cat_name_lower, 'đèn') !== false) {
                    $icon_color = '#ffc107'; // Vàng (Warning) - Dùng mã này cho đậm đà
                } elseif (strpos($cat_name_lower, 'đường') !== false || strpos($cat_name_lower, 'ổ gà') !== false) {
                    $icon_color = '#343a40'; // Đen/Xám đậm (Dark)
                } elseif (strpos($cat_name_lower, 'rác') !== false) {
                    $icon_color = '#0dcaf0'; // Xanh dương sáng (Info)
                } elseif (strpos($cat_name_lower, 'khác') !== false) {
                    $icon_color = '#6f42c1'; // Tím mộng mơ
                }
                // -------------------------------

                echo '<li class="nav-item">';
                // Thêm style="color: ..." vào thẻ <i>
                echo '<a class="nav-link category-filter-link" href="index.php?category=' . $cat_sidebar['id'] . '">';
                echo '<i class="fas ' . htmlspecialchars($cat_icon_sidebar) . ' fa-fw" style="color: ' . $icon_color . '!important;"></i> ';
                echo htmlspecialchars($cat_sidebar['name']);
                echo '</a>';
                echo '</li>';
            }
        } catch (Exception $e) {
            echo '<li class="nav-item px-3 text-danger">Lỗi tải danh mục</li>';
        }
    }
?>
                    
                    <li class="nav-item mt-3 border-top pt-3">
                        <h6 class="sidebar-heading px-3 mb-2 text-muted">Lối tắt</h6>
                    </li>            
                    <li class="nav-item ">
                        <a class="nav-link text-danger fw-bold" href="logout.php">
                            <i class="fas fa-sign-out-alt fa-fw"></i> Đăng xuất
                        </a>
                    </li>

                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <li class="nav-item mt-3 border-top pt-3">
                        <a class="nav-link admin-link" href="admin/index.php">
                            <i class="fas fa-user-shield fa-fw"></i> Trang Quản trị
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-6 col-md-10">

            <div class="create-report-wrapper" data-bs-toggle="modal" data-bs-target="#reportModal">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-2">
                        <?php if (!empty($_SESSION['user_avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar_url']); ?>" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-user text-secondary"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fake-input">
                            Bạn thấy sự cố gì, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_fullname'])[0]); ?>?
                        </div>
                    </div>
                </div>
                <hr class="my-2 opacity-25">
                <div class="row gx-1 text-center">
                    <div class="col">
                        <div class="action-btn">
                            <i class="fas fa-camera text-success me-1"></i> Ảnh/Video
                        </div>
                    </div>
                    <div class="col">
                        <div class="action-btn">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i> Check-in
                        </div>
                    </div>
                    <div class="col">
                        <div class="action-btn">
                            <i class="far fa-smile text-warning me-1"></i> Cảm xúc
                        </div>
                    </div>
                </div>
            </div>
            <h3><i class="fas fa-newspaper"></i> Báo cáo mới nhất</h3>
            <hr>

            <div id="newsfeed-container">
                </div>
        </div>

        <div class="col-lg-3 d-none d-lg-block">
            <div class="sidebar-right sticky-sidebar">
                
                <div class="widget-card mb-4 shadow-sm bg-white p-3 rounded">
                    <h5 class="mb-3 fw-bold"><i class="fas fa-map-marked-alt text-success"></i> Bản đồ tổng quan</h5>
                    <div id="map-summary" style="height: 250px; border-radius: 12px; overflow: hidden;"></div>
                </div>

                <div class="card shadow-sm mb-3 border-0 rounded-4">
                    <div class="card-header bg-white border-0 pb-0 pt-3">
                        <h6 class="fw-bold"><i class="fas fa-calendar-day me-2 text-primary"></i>Hôm nay có gì?</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold text-danger">Mới tiếp nhận</span>
                                <span class="small fw-bold"><?php echo $stat_today_new; ?></span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $pct_new; ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold text-warning">Đang xử lý</span>
                                <span class="small fw-bold"><?php echo $stat_today_processing; ?></span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pct_processing; ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold text-success">Đã hoàn thành</span>
                                <span class="small fw-bold"><?php echo $stat_today_completed; ?></span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $pct_completed; ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if($total_today == 0): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted fst-italic">Chưa có báo cáo nào hôm nay.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div id="chatbot-floating-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button class="btn btn-primary rounded-circle shadow-lg d-flex justify-content-center align-items-center" 
            id="chatbot-toggle-btn"
            style="width: 60px; height: 60px; font-size: 24px; border: none; background-color: #00cba9;"> 
            <i class="fas fa-robot text-white"></i>
    </button>
</div>

<?php
// Include footer
include 'footer.php';
?>

<script>
$(document).ready(function() {
    if ($('#report_image_input').length) {
        
        // Xóa giá trị cũ khi click để chọn lại được ảnh
        $('#report_image_input').on('click', function() {
            this.value = null; 
        });

        $('#report_image_input').off('change').on('change', function(event) {
            const file = event.target.files[0];
            const catSelect = $('#category_id'); 
            
            if (!file) return;

            const previousValue = catSelect.val();

            // Hiện trạng thái Loading
            catSelect.prepend('<option value="LOADING_AI" selected>🧠 AI Gemini đang soi...</option>');
            catSelect.val('LOADING_AI');
            catSelect.prop('disabled', true);

            let formData = new FormData();
            formData.append('image', file);

            $.ajax({
                url: 'api/analyze_image.php', 
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    // Xóa dòng loading
                    catSelect.find('option[value="LOADING_AI"]').remove();
                    catSelect.prop('disabled', false);

                    // --- BẮT LỖI Ở ĐÂY ---
                    if (res.success === false) {
                        // Trường hợp PHP bắt được lỗi (VD: Sai Key, Model không tồn tại...)
                        alert("❌ Lỗi AI trả về: " + res.message);
                        catSelect.val(previousValue);
                        return;
                    }

                    if (res.success && res.suggested_id) {
                        // Tìm xem ID có tồn tại trong select không
                        if (catSelect.find(`option[value='${res.suggested_id}']`).length > 0) {
                            // CÓ: Chọn luôn
                            catSelect.val(res.suggested_id).change();
                            catSelect.css({'border': '2px solid #28a745', 'box-shadow': '0 0 10px #28a745'});
                            setTimeout(() => { catSelect.css({'border': '', 'box-shadow': ''}); }, 2000);
                        } else {
                            // KHÔNG: Báo lỗi lệch ID
                            let cacIdDangCo = [];
                            catSelect.find('option').each(function() {
                                if($(this).val()) cacIdDangCo.push("ID " + $(this).val() + ": " + $(this).text());
                            });
                            alert(`⚠️ LỆCH ID!\n\nAI bảo là ID: ${res.suggested_id}\nNhưng web chỉ có:\n${cacIdDangCo.join('\n')}`);
                            catSelect.val(previousValue);
                        }
                    } else {
                        // AI chạy thành công nhưng không thấy lỗi gì trong ảnh
                        alert("⚠️ AI không thấy sự cố nào rõ ràng trong ảnh này.");
                        catSelect.val(previousValue);
                    }
                },
                error: function(xhr, status, error) {
                    // Lỗi mạng hoặc lỗi code PHP nghiêm trọng (Cú pháp, 500...)
                    catSelect.find('option[value="LOADING_AI"]').remove();
                    catSelect.prop('disabled', false);
                    catSelect.val(previousValue);
                    
                    alert("❌ Lỗi KẾT NỐI (Ajax):\n" + error + "\n\nPhản hồi từ server:\n" + xhr.responseText);
                }
            });

            // 4. Xử lý GPS (nếu có thư viện EXIF)
            if (typeof EXIF !== 'undefined') {
                EXIF.getData(file, function() {
                    const lat = EXIF.getTag(this, "GPSLatitude");
                    const latRef = EXIF.getTag(this, "GPSLatitudeRef");
                    const lng = EXIF.getTag(this, "GPSLongitude");
                    const lngRef = EXIF.getTag(this, "GPSLongitudeRef");

                    if (lat && lng && latRef && lngRef) {
                        const convertDMSToDD = (d, m, s, ref) => {
                            let dd = d + m / 60 + s / 3600;
                            if (ref == "S" || ref == "W") dd = dd * -1;
                            return dd;
                        };
                        
                        const decimalLat = convertDMSToDD(lat[0], lat[1], lat[2], latRef);
                        const decimalLng = convertDMSToDD(lng[0], lng[1], lng[2], lngRef);
                        
                        $('#report_image_input').data('gps-found', true);
                        if (typeof updateMapAndMarker === "function") {
                            updateMapAndMarker({ lat: decimalLat, lng: decimalLng });
                            console.log("Đã cập nhật vị trí từ ảnh");
                        }
                    }
                });
            }
        });
    }
});
</script>