<?php 
// 1. Bao gồm header (đã tự động kiểm tra admin)
include 'header_admin.php'; 
?>

<div class="page-card">

    <h1 class="h2">Quản lý Người Dùng</h1>
    <p>Nơi xử lý, khóa, mở khóa hoặc xóa tài khoản người dùng.</p>

    <div class="table-responsive">
        <table class="table main-table table-hover align-middle">
            <thead> <tr>
                    <th>ID</th>
                    <th>Avatar</th>
                    <th>Tên người dùng</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Hành động</th> </tr>
            </thead>
            <tbody id="user-table-body">
                <tr>
                    <td colspan="6" class="text-center p-5">
                        <span class="spinner-border spinner-border-sm"></span> Đang tải danh sách người dùng...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div> <script>

// Hàm tiện ích (giữ nguyên)
function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;');
}

// ----- HÀM CHẠY CHÍNH -----
$(document).ready(function() {

    // 1. Định nghĩa hàm tải danh sách người dùng
    function loadUsers() {
        let tableBody = $('#user-table-body');
        tableBody.html('<tr><td colspan="6" class="text-center p-5"><span class="spinner-border spinner-border-sm"></span> Đang tải...</td></tr>');

        $.ajax({
            url: 'api/get_users.php', // Gọi API lấy người dùng
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    tableBody.html('<tr><td colspan="6" class="text-danger text-center p-5">Lỗi: ' + response.message + '</td></tr>');
                    return;
                }

                tableBody.empty(); // Xóa spinner
                let users = response.users;

                if (users.length === 0) {
                    tableBody.html('<tr><td colspan="6" class="text-center p-5">Không tìm thấy người dùng nào.</td></tr>');
                    return;
                }

                users.forEach(function(user) {
                    
                    // === SỬA JAVASCRIPT ĐỂ DÙNG BADGE MỚI ===
                    let statusHtml = '';
                    if (user.is_active == 1) {
                        // Dùng style "Đã hoàn thành" (xanh lá) cho "Hoạt động"
                        statusHtml = '<span class="badge rounded-pill badge-status status-da-hoan-thanh"><i class="fas fa-check-circle"></i> Hoạt động</span>';
                    } else {
                        // Dùng style "Không hợp lệ" (đỏ) cho "Đã khóa"
                        statusHtml = '<span class="badge rounded-pill badge-status status-khong-hop-le"><i class="fas fa-lock"></i> Đã khóa</span>';
                    }
                    
                    // === SỬA JAVASCRIPT ĐỂ DÙNG NÚT ICON MỚI ===
                    let actionButtonHtml = '';

                    // Nút Khóa/Mở
                    if (user.is_active == 1) {
                        // Nút Khóa (Style màu cam/vàng)
                        actionButtonHtml = `<button class="btn btn-lock" data-id="${user.id}" data-new-status="0" title="Khóa người dùng" style="background-color: #fff7e6; color: #ffa117;"><i class="fas fa-lock"></i></button> `;
                    } else {
                        // Nút Mở (Style màu xanh lá)
                        actionButtonHtml = `<button class="btn btn-update btn-unlock" data-id="${user.id}" data-new-status="1" title="Mở khóa"><i class="fas fa-lock-open"></i></button> `;
                    }
                    
                    // Nút Xóa (Style màu đỏ)
                    actionButtonHtml += `<button class="btn btn-delete" data-id="${user.id}" title="Xóa người dùng"><i class="fas fa-trash"></i></button>`;

                    // Xử lý avatar (dùng ảnh mặc định nếu không có)
                    let avatarPath = (user.avatar_url) ? `../${htmlspecialchars(user.avatar_url)}` : '../assets/img/default-avatar.png'; 
                    if (user.avatar_url) {
                    // Kiểm tra nếu là Link Google (bắt đầu bằng http hoặc https)
                    if (user.avatar_url.startsWith('http') || user.avatar_url.startsWith('https')) {
                        avatarPath = user.avatar_url;
                        } else {
                        // Nếu là ảnh upload nội bộ thì thêm đường dẫn thư mục
                        avatarPath = `../${user.avatar_url}`;
                        }
                    }
                    // Tạo hàng cho bảng
                    let rowHtml = `
                        <tr>
                            <td>${user.id}</td>
                            <td>
                                <img src="${avatarPath}" alt="Avatar" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                            </td>
                            <td>${htmlspecialchars(user.fullname)}</td>
                            <td>${htmlspecialchars(user.email)}</td>
                            <td>${statusHtml}</td>
                            <td class="action-buttons text-end">${actionButtonHtml}</td>
                        </tr>
                    `;
                    tableBody.append(rowHtml);
                });
            },
            error: function() {
                tableBody.html('<tr><td colspan="6" class="text-danger text-center p-5">Lỗi máy chủ khi tải người dùng.</td></tr>');
            }
        });
    }

    // 2. Tải người dùng ngay khi trang sẵn sàng
    loadUsers();

    // 3. Xử lý sự kiện khi nhấp vào nút "Khóa" hoặc "Mở"
    $('#user-table-body').on('click', '.btn-lock, .btn-unlock', function() {
        let button = $(this);
        let userId = button.data('id');
        let newStatus = button.data('new-status');
        let actionText = (newStatus == 0) ? "khóa" : "mở khóa";
        let iconType = (newStatus == 0) ? "warning" : "success";
        let btnColor = (newStatus == 0) ? "#ffc107" : "#198754";

        Swal.fire({
            title: `Bạn chắc chắn muốn ${actionText}?`,
            text: `Người dùng này (ID: ${userId}) sẽ bị ${actionText}.`,
            icon: iconType,
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Đồng ý, ${actionText}!`,
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                // Nếu người dùng đồng ý, mới chạy AJAX
                $.ajax({
                    url: 'api/update_user_status.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: userId,
                        new_status: newStatus
                    }),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Đã cập nhật!',
                                'Trạng thái người dùng đã được thay đổi.',
                                'success'
                            );
                            loadUsers(); // Tải lại
                        } else {
                            Swal.fire('Lỗi!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi!', 'Lỗi máy chủ khi cập nhật.', 'error');
                    }
                });
            }
        });
    });

    // 4. Xử lý sự kiện nút "Xóa"
    $('#user-table-body').on('click', '.btn-delete', function() {
        let userId = $(this).data('id');
        
        Swal.fire({
            title: 'Bạn có chắc chắn muốn xóa?',
            text: `CẢNH BÁO: Người dùng (ID: ${userId}) và TẤT CẢ báo cáo của họ sẽ bị XÓA VĨNH VIỄN. Bạn không thể hoàn tác!`,
            icon: 'error', // Icon Cảnh báo nguy hiểm
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý, XÓA!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                // Nếu người dùng đồng ý, mới chạy AJAX
                $.ajax({
                    url: 'api/delete_user.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ user_id: userId }),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Đã xóa!',
                                'Người dùng đã bị xóa khỏi hệ thống.',
                                'success'
                            );
                            loadUsers(); // Tải lại
                        } else {
                            Swal.fire('Lỗi!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi!', 'Lỗi máy chủ. Không thể xóa.', 'error');
                    }
                });
            }
        });
    });

});
</script>

<?php 
// 2. Bao gồm footer
include 'footer_admin.php'; 
?>