<?php
// 1. Include header
include 'header.php'; 

// 2. Protect page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 3. Lấy thông tin
$user_fullname = htmlspecialchars($_SESSION['user_fullname'] ?? 'User');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? 'email@example.com');
$user_role = htmlspecialchars($_SESSION['user_role'] ?? 'user');
$user_avatar = htmlspecialchars($_SESSION['user_avatar_url'] ?? 'assets/images/default_avatar.png');

// 4. Xử lý avatar
if (strpos($user_avatar, 'ui-avatars.com') !== false) {
    $user_avatar_large = "https://ui-avatars.com/api/?name=" . urlencode($user_fullname) . "&background=00A78E&color=fff&size=150";
} else {
    $user_avatar_large = $user_avatar;
}
?>

<style>
    /* Card Profile đẹp hơn */
    .profile-card {
        border: none;
        border-radius: 16px; /* Bo góc mềm mại */
        background: #fff;
        transition: transform 0.2s;
    }
    
    /* Ảnh đại diện nổi bật */
    .profile-avatar-large {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff; /* Viền trắng */
        box-shadow: 0 5px 15px rgba(0,0,0,0.15); /* Bóng mờ */
    }

    /* Nút camera edit */
    .avatar-edit-button {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        bottom: 0;
        right: 0;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.2s;
    }
    .avatar-edit-button:hover {
        background: #f0f0f0;
        transform: scale(1.1);
    }

    /* Badge cho Role */
    .role-badge {
        font-size: 0.8rem;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Phần thống kê dạng lưới */
    .stat-box {
        text-align: center;
        padding: 10px 5px;
        border-radius: 10px;
        transition: background 0.2s;
    }
    .stat-box:hover {
        background-color: #f8f9fa;
    }
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: #333;
        display: block;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center"> 
        <div class="col-lg-4 mb-4">
            <div class="card shadow profile-card sticky-top" style="top: 90px;">
                <div class="position-absolute top-0 end-0 p-3">
                    <button class="btn btn-sm btn-light text-muted rounded-circle" title="Cài đặt / Đổi mật khẩu">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>

                <div class="card-body text-center pt-5 pb-4"> 
                    <div class="position-relative d-inline-block mb-3">
                        <img src="<?php echo $user_avatar_large; ?>" alt="Avatar" class="profile-avatar-large" id="current-avatar-img">
                        <label for="avatar-upload-input" class="avatar-edit-button" title="Đổi ảnh đại diện">
                            <i class="fas fa-camera text-secondary" style="font-size: 14px;"></i>
                        </label>
                        <input type="file" id="avatar-upload-input" hidden accept="image/png, image/jpeg, image/gif">
                    </div>
                    
                    <h4 class="fw-bold mb-1"><?php echo $user_fullname; ?></h4>
                    <p class="text-muted small mb-2"><?php echo $user_email; ?></p>
                    
                    <span class="badge bg-light text-primary border border-primary-subtle role-badge mb-3">
                        <?php echo $user_role; ?>
                    </span> 

                    <hr class="my-4 opacity-25">
                    
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="stat-box">
                                <span class="stat-number" id="total-reports">0</span>
                                <span class="stat-label">Tổng</span>
                            </div>
                        </div>
                        <div class="col-4 border-start border-end">
                            <div class="stat-box">
                                <span class="stat-number text-primary" id="processing-reports">0</span>
                                <span class="stat-label">Xử lý</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <span class="stat-number text-success" id="completed-reports">0</span>
                                <span class="stat-label">Xong</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold m-0"><i class="fas fa-stream me-2 text-primary"></i>Nhật ký hoạt động</h4>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="sortDropdownButton" data-bs-toggle="dropdown">
                        Mới nhất
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadMyReports('DESC', 'Mới nhất')">Mới nhất</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadMyReports('ASC', 'Cũ nhất')">Cũ nhất</a></li>
                    </ul>
                </div>
                
                </div>
            
            <div id="my-reports-container">
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted mb-0">Đang tải dữ liệu của bạn...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="avatarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm"> 
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <h5 class="fw-bold mb-3">Đổi ảnh đại diện?</h5>
                <div class="mb-3">
                    <img id="avatar-preview-img" src="#" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <div id="avatar-upload-alert" class="alert d-none mt-2 p-2 small"></div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="button" class="btn btn-primary fw-bold" id="save-avatar-button">Lưu thay đổi</button>
                    <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Hủy bỏ</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer (Nơi chứa hàm loadMyReports chuẩn)
include 'footer.php';
?>